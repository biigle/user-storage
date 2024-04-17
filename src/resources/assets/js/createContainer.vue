<script>
import { get } from '@biigle/ol/proj';
import DirectoriesApi from './api/storageRequestDirectories';
import FilesApi from './api/storageRequestFiles';
import StorageRequestApi from './api/storageRequests';
import {LoaderMixin, handleErrorResponse, FileBrowserComponent} from './import';
import {sizeForHumans} from './utils';

// Number of times a file upload is retried.
const RETRY_UPLOAD = 3;

export default {
    mixins: [LoaderMixin],
    components: {
        fileBrowser: FileBrowserComponent,
    },
    data() {
        return {
            currentUploadedSize: 0,
            files: [],
            finished: false,
            finishedUploadedSize: 0,
            finishedChunksSize: 0,
            loadedUnfinishedRequest: false,
            maxSize: -1,
            rootDirectory: {
                name: '',
                directories: {},
                files: [],
                selected: false,
            },
            selectedDirectory: null,
            storageRequest: null,
            availableQuotaBytes: 0,
            maxFilesizeBytes: 0,
            exceedsMaxFilesize: false,
            chunkSize: 0,
            pathContainsSpaces: false,
            failedFiles: [],
            finishIncomplete: false,
        };
    },
    computed: {
        hasSelectedDirectory() {
            return this.selectedDirectory !== null;
        },
        selectedDirectoryName() {
            return this.selectedDirectory.name;
        },
        hasFiles() {
            return this.files.length > 0;
        },
        totalSize() {
            return this.files.reduce(function (carry, file) {
                return carry + file.size;
            }, 0);
        },
        totalSizeToUpload() {
            return this.files.reduce(function (carry, file) {
                if (file.saved) {
                    return carry;
                }

                return carry + file.size;
            }, 0);
        },
        totalSizeToUploadForHumans() {
            return sizeForHumans(this.totalSizeToUpload);
        },
        totalSizeForHumans() {
            return sizeForHumans(this.totalSize);
        },
        uploadedSize() {
            return this.currentUploadedSize + this.finishedUploadedSize + this.finishedChunksSize;
        },
        uploadedPercent() {
            return Math.round(this.uploadedSize / this.totalSizeToUpload * 100);
        },
        uploadedSizeForHumans() {
            return sizeForHumans(this.uploadedSize);
        },
        editable() {
            return !this.loading && !this.finished;
        },
        exceedsMaxSize() {
            return this.availableQuotaBytes !== -1  && this.totalSize > this.availableQuotaBytes;
        },
        canSubmit() {
            return this.hasFiles && !this.exceedsMaxSize;
        },
        availableQuota() {
            return sizeForHumans(this.availableQuotaBytes);
        },
        maxFilesize() {
            return sizeForHumans(this.maxFilesizeBytes);
        },
    },
    methods: {
        handleFilesChosen(event) {
            // Force users to create new directories for their files. Otherwise they
            // could upload all their files in the same directory in multiple storage
            // requests, which should be avoided.
            if (!this.hasSelectedDirectory) {
                return;
            }

            let newFiles = Array.from(event.target.files).filter((file) => {
                return file.size <= this.maxFilesizeBytes;
            });

            if (newFiles.length < event.target.files.length) {
                this.exceedsMaxFilesize = true;
            }

            let files = this.selectedDirectory.files;
            let i = 0;

            let newNames = [];
            for (i = 0; i < newFiles.length; i++) {
                newNames.push(newFiles[i].name);
            }

            // Remove previously added files with the same name. They will be replaced
            // with the new files.
            i = files.length;
            while (i--) {
                if (newNames.includes(files[i].name)) {
                    files.splice(i, 1);
                }
            }

            for (i = 0; i < newFiles.length; i++) {
                // Replace spaces by underscores in file name due to error when uploading files >5GB.
                // See https://github.com/biigle/user-storage/issues/16.
                let file = newFiles[i];
                if (file.name.includes(' ')) {
                    this.pathContainsSpaces = true;
                    let newName = newFiles[i].name.replace(/ /g, '_');
                    file = new File([newFiles[i]], newName, { type: newFiles[i].type });
                }
                files.push(file);
            }

            this.syncFiles();
        },
        getNewDirectory(name) {
            return {
                name: name,
                directories: {},
                files: [],
                selected: false,
            };
        },
        handleNewDirectory(path, root) {
            let newDirectory;
            let directories = this.rootDirectory.directories;
            if (this.hasSelectedDirectory) {
                if (!root) {
                    directories = this.selectedDirectory.directories;
                }
                this.selectedDirectory.selected = false;
            }

            // Windows-style directory separators are converted before.
            this.sanitizePath(path).split('/').forEach((name) => {
                if (!directories.hasOwnProperty(name)) {
                    newDirectory = Vue.set(
                        directories,
                        name,
                        this.getNewDirectory(name)
                    );
                }

                directories = directories[name].directories;
            });

            newDirectory.selected = true;
            this.selectedDirectory = newDirectory;
        },
        addDirectory(root) {
            let name = prompt('Please enter the new directory name');
            if (name) {
                if (name.includes(' ')) {
                    this.pathContainsSpaces = true;
                    name = name.replace(/ /g, '_');
                }
                this.handleNewDirectory(name, root === true);
            }
        },
        addRootDirectory() {
            this.addDirectory(true);
        },
        addFiles() {
            this.$refs.fileInput.click();
        },
        selectDirectory(directory) {
            if (this.hasSelectedDirectory) {
                this.selectedDirectory.selected = false;
            }

            this.selectedDirectory = directory;
            this.selectedDirectory.selected = true;
        },
        unselectDirectory() {
            if (this.hasSelectedDirectory) {
                this.selectedDirectory.selected = false;
                this.selectedDirectory = null;
            }
        },
        removeDirectory(directory, path) {
            // Remove the leading slash from the path.
            path = path.slice(1);
            let directories = this.rootDirectory.directories;
            let breadcrumbs = path.split('/').slice(0, -1);
            breadcrumbs.forEach(function (name) {
                directories = directories[name].directories;
            });

            let promise;
            // Handle case where the directory was previously uploaded and should
            // actually be deleted.
            let hasSavedFiles = directories[directory.name].files.reduce(function (c, f) {
                return c || f.saved === true;
            }, false);
            if (hasSavedFiles) {
                promise = DirectoriesApi.delete({id: this.storageRequest.id}, {directories: [path]});
            } else {
                promise = Vue.Promise.resolve();
            }

            promise.then(() => {
                Vue.delete(directories, directory.name);
                if (this.hasSelectedSubdirectory(directory)) {
                    this.selectedDirectory = null;
                }

                this.syncFiles();
            }, handleErrorResponse);
        },
        hasSelectedSubdirectory(directory) {
            return Object.keys(directory.directories).reduce((carry, key) => {
                return carry || this.hasSelectedSubdirectory(directory.directories[key]);
            }, directory.selected);
        },
        removeFile(file, path) {
            // Remove the leading slash from the path.
            path = path.slice(1);
            let directory = this.rootDirectory;
            let breadcrumbs = path.split('/');
            breadcrumbs.forEach(function (name) {
                directory = directory.directories[name];
            });
            let files = directory.files;
            // Handle case where the file was previously uploaded and should actually be
            // deleted.
            let promise;
            if (file.saved) {
                promise = FilesApi.delete({id: file.id});
            } else {
                promise = Vue.Promise.resolve();
            }

            promise.then(() => {
                let index = files.indexOf(file);
                if (index !== -1) {
                    files.splice(index, 1);
                }

                this.syncFiles();
            }, handleErrorResponse);
        },
        extractFiles(directory, prefix) {
            prefix = prefix ? `${prefix}/${directory.name}` : directory.name;

            let files = [];
            for (let key in directory.directories) {
                files = files.concat(this.extractFiles(directory.directories[key], prefix))
            }

            return files.concat(directory.files.map(function (file) {
                return {
                    prefix: prefix,
                    size: file.size,
                    file: file,
                    directory: directory,
                };
            }));
        },
        syncFiles() {
            this.files = this.extractFiles(this.rootDirectory);
        },
        handleSubmit(reupload) {
            if (!this.canSubmit) {
                return;
            }

            this.startLoading();
            // Reuse already created storage request in case something went wrong.
            let promise = this.storageRequest && !reupload
                ? Promise.resolve({ body: this.storageRequest })
                : StorageRequestApi.save();

            let files = reupload ? this.getFailedFiles() : this.files;
            

            promise.then((res) => this.proceedWithUpload(res, files))
                .then(() => { this.finishIncomplete = this.getFailedFiles().length > 0 })
                .then(() => this.maybeFinishSubmission(files.length))
                .catch(handleErrorResponse)
                .finally(this.finishLoading);
        },
        getFailedFiles(){
            return this.files.filter(f => f.failed);
        },
        proceedWithUpload(response, files) {
            this.storageRequest = response.body;

            return this.uploadAllFiles(files);
        },
        uploadAllFiles(files) {
            // Exclude files initialized from an unfinished request.
            let queue = files.filter(f => f.file.saved !== true);
            let loadNextFile = () => {
                if (queue.length === 0) {
                    return;
                }

                return this.uploadFile(queue.shift()).then(loadNextFile);
            };

            return loadNextFile();
        },
        uploadFile(file) {
            this.currentUploadedSize = 0;

            let updateFinishedSize = function (response) {
                this.currentUploadedSize = 0;
                this.finishedChunksSize = 0;
                this.finishedUploadedSize += file.file.size;

                return response;
            };

            let saveFailedFiles = () => { file.failed = true };

            if (file.file.size > this.chunkSize) {
                return this.uploadChunkedFile(file)
                .then(() => {
                    if(file.failed){
                        delete file.failed;

                    }
                }, saveFailedFiles)
                .then(updateFinishedSize);
            }

            return this.uploadBlob(file.file, file.prefix)
                .then(function (response) {
                    // Set saved to handle these files and directories differently when
                    // they should be deleted.
                    file.file.saved = true;
                    file.file.id = response.body.id;
                    if(file.failed){
                        delete file.failed;
                    }
                }, saveFailedFiles)
                .then(updateFinishedSize);
        },
        uploadChunkedFile(file) {
            this.finishedChunksSize = 0;
            let prefix = file.prefix;
            file = file.file;

            let start = 0;
            let chunkIndex = 0;
            let totalChunks = Math.ceil(file.size / this.chunkSize);
            let uploadNextChunk = (loop) => {
                if (start === file.size) {
                    return Vue.Promise.resolve();
                }

                let end = Math.min(start + this.chunkSize, file.size);
                let chunk = new File([file.slice(start, end)], file.name, {
                    type: file.type,
                    lastModified: file.lastModified,
                });
                let promise = this.uploadBlob(chunk, prefix, chunkIndex, totalChunks);
                start = end;
                chunkIndex += 1;

                promise.then(function () {
                    this.finishedChunksSize += chunk.size;
                },
                function (e) {
                    // Delete the whole file if any chunk upload fails. The file is
                    // retried again next time. There is no easy way to resume a
                    // chunked file that partly failed during upload.
                    if (file.id !== undefined) {
                        if (this.files.filter(f => f.file.saved).length > 1) {
                            FilesApi.delete({id: file.id})
                        } else {
                            // If this is the only saved file, we must delete the
                            // whole storage request.
                            StorageRequestApi.delete({id: this.storageRequest.id});
                            this.storageRequest = null;
                        }
                        delete file.id;
                        file.saved = false;
                    }

                });

                if (loop) {
                    return promise.then(uploadNextChunk);
                }

                return promise;
            }

            return uploadNextChunk()
                .then(function (response) {
                    // Set saved to handle these files and directories differently when
                    // they should be deleted.
                    file.saved = true;
                    file.id = response.body.id;
                })
                .then(() => uploadNextChunk(true));
        },
        uploadBlob(blob, prefix, chunkIndex, totalChunks, retryCount) {
            retryCount = retryCount || 1;

            let data = new FormData();
            data.append('file', blob);
            data.append('prefix', prefix);

            if (chunkIndex !== undefined && totalChunks !== undefined) {
                data.append('chunk_index', chunkIndex);
                data.append('chunk_total', totalChunks);
            }

            let url = `api/v1/storage-requests/${this.storageRequest.id}/files`;

            // Don't use the API resource object because it does not allow tracking of
            // the upload progress.
            return this.$http.post(url, data, {
                    uploadProgress: this.updateCurrentUploadedSize
                })
                .catch((e) => {
                    // Try uploading again on server error until number of retries is
                    // reached.
                    if (e.status >= 500 && retryCount < RETRY_UPLOAD) {
                        // Add delay to prevent failing uploads due to e.g. BIIGLE instance updates or
                        // short moments of unavailability.
                        return new Vue.Promise((resolve) => {
                            setTimeout(() => resolve(this.uploadBlob(blob, prefix, chunkIndex, totalChunks, retryCount + 1)), 5000);
                        });
                    }
                    throw e;
                });
        },
        updateCurrentUploadedSize(event) {
            if (event.lengthComputable) {
                this.currentUploadedSize = event.loaded;
            }
        },
        maybeFinishSubmission(failedFileCount) {
            // Nothing could be submitted so there is nothing to finish
            if (failedFileCount === this.getFailedFiles().length) {
                return;
            }
            return StorageRequestApi.update({id: this.storageRequest.id}, {})
                .then(() => this.finished = !this.finishIncomplete, handleErrorResponse);
        },
        addExistingFiles(files) {
            files.forEach(this.addExistingFile);
            this.syncFiles();
        },
        addExistingFile(file) {
            let path = file.path;
            let breadcrumbs = path.split('/');
            let filename = breadcrumbs.pop();
            let currentDirectory = this.rootDirectory;
            breadcrumbs.forEach((dirname) => {
                if (!currentDirectory.directories.hasOwnProperty(dirname)) {
                    Vue.set(currentDirectory.directories, dirname, this.getNewDirectory(dirname));
                }
                currentDirectory = currentDirectory.directories[dirname];
            });

            currentDirectory.files.push({
                saved: true,
                name: filename,
                size: file.size,
                id: file.id,
            });
        },
        sanitizePath(path) {
            // Scnchronize this with Rules/FilePrefix.php.

            // Convert Windows directory separators to Unix.
            path = path.replace(/\\/g, '/');

            // Remove all characters that we don't want to see in a directory name
            // (except "/"" directory separators that are removed later).
            // \p{Letter} is a unicode property escape, see:
            // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions/Unicode_Property_Escapes
            path = path.replace(/[^\p{L}\p{N}\-_ /.()[\]]/ug, '');

            // Trim unwanted characters
            path = path.replace(/^[^\p{L}\p{N}]/ug, '');
            path = path.replace(/[^\p{L}\p{N})\]]$/ug, '');

            // Remove double slashes.
            path = path.replace(/\/+/g, '/');

            return path;
        },
    },
    created() {
        this.availableQuotaBytes = biigle.$require('user-storage.availableQuota');
        this.maxFilesizeBytes = biigle.$require('user-storage.maxFilesize');
        this.chunkSize = biigle.$require('user-storage.chunkSize');
        // This remains null if no previous request exists.
        this.storageRequest = biigle.$require('user-storage.previousRequest');
        if (this.storageRequest && this.storageRequest.files.length > 0) {
            this.addExistingFiles(this.storageRequest.files);
            this.loadedUnfinishedRequest = true;
        }

        window.addEventListener('beforeunload', (e) => {
            if (this.loading) {
                e.preventDefault();
                e.returnValue = '';

                return 'This page is asking you to confirm that you want to leave - the file upload is still in progress.';
            }
        });
    },
};
</script>
