<script>
import StorageRequestApi from './api/storageRequests';
import {LoaderMixin, handleErrorResponse, FileBrowserComponent} from './import';
import {sizeForHumans} from './utils';

export default {
    mixins: [LoaderMixin],
    components: {
        fileBrowser: FileBrowserComponent,
    },
    data() {
        return {
            maxSize: -1,
            files: [],
            rootDirectory: {
                name: '',
                directories: {},
                files: [],
                selected: false,
            },
            selectedDirectory: null,
            currentUploadedSize: 0,
            finishedUploadedSize: 0,
            finished: false,
            storageRequest: null,
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
        totalSizeForHumans() {
            return sizeForHumans(this.totalSize);
        },
        uploadedSize() {
            return this.currentUploadedSize + this.finishedUploadedSize;
        },
        uploadedPercent() {
            return Math.round(this.uploadedSize / this.totalSize * 100);
        },
        uploadedSizeForHumans() {
            return sizeForHumans(this.uploadedSize);
        },
        editable() {
            return !this.loading && !this.finished;
        },
        exceedsMaxSize() {
            return this.maxSize !== -1  && this.totalSize > this.maxSize;
        },
        canSubmit() {
            return this.hasFiles && !this.exceedsMaxSize;
        },
        maxSizeForHumans() {
            return sizeForHumans(this.maxSize);
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

            let newFiles = event.target.files;
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
                files.push(newFiles[i]);
            }

            this.syncFiles();
        },
        handleNewDirectory(path) {
            let newDirectory;
            let directories = this.rootDirectory.directories;
            if (this.hasSelectedDirectory) {
                directories = this.selectedDirectory.directories;
                this.selectedDirectory.selected = false;
            }

            path.split('/').forEach(function (name) {
                if (!directories.hasOwnProperty(name)) {
                    newDirectory = Vue.set(directories, name, {
                        name: name,
                        directories: {},
                        files: [],
                        selected: false,
                    });
                }

                directories = directories[name].directories;
            });

            newDirectory.selected = true;
            this.selectedDirectory = newDirectory;
        },
        addDirectory() {
            let name = prompt('Please enter the new directory name');
            if (name) {
                this.handleNewDirectory(name);
            }
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
            let directories = this.rootDirectory.directories;
            let breadcrumbs = path.split('/').slice(1, -1);
            breadcrumbs.forEach(function (name) {
                directories = directories[name].directories;
            });
            Vue.delete(directories, directory.name);
            if (this.hasSelectedSubdirectory(directory)) {
                this.selectedDirectory = null;
            }

            this.syncFiles();
        },
        hasSelectedSubdirectory(directory) {
            return Object.keys(directory.directories).reduce((carry, key) => {
                return carry || this.hasSelectedSubdirectory(directory.directories[key]);
            }, directory.selected);
        },
        removeFile(file, path) {
            let directory = this.rootDirectory;
            let breadcrumbs = path.split('/').slice(1);
            breadcrumbs.forEach(function (name) {
                directory = directory.directories[name];
            });
            let files = directory.files;
            let index = files.indexOf(file);
            if (index !== -1) {
                files.splice(index, 1);
            }

            this.syncFiles();
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
                };
            }));
        },
        syncFiles() {
            this.files = this.extractFiles(this.rootDirectory);
        },
        handleSubmit() {
            if (!this.canSubmit) {
                return;
            }

            this.startLoading();
            // Reuse already created storage request in case something went wrong.
            let promise = this.storageRequest
                ? Promise.resolve({body: this.storageRequest})
                : StorageRequestApi.save();

            promise.then(this.proceedWithUpload)
                .then(this.finishSubmission)
                .catch(handleErrorResponse)
                .finally(this.finishLoading);
        },
        proceedWithUpload(response) {
            this.storageRequest = response.body;

            return this.uploadAllFiles();
        },
        uploadAllFiles() {
            let queue = this.files.slice();
            let loadNextFile = () => {
                if (queue.length === 0) {
                    return;
                }

                return this.uploadFile(queue.shift()).then(loadNextFile);
            };

            return loadNextFile();
        },
        uploadFile(file) {
            let url = `api/v1/storage-requests/${this.storageRequest.id}/files`;
            let data = new FormData();
            data.append('file', file.file);
            data.append('prefix', file.prefix);

            // Don't use the API resource object because it does not allow tracking of
            // the upload progress.
            return this.$http.post(url, data, {
                    uploadProgress: this.updateCurrentUploadedSize
                })
                .then(() => {
                    this.currentUploadedSize = 0;
                    this.finishedUploadedSize += file.file.size;
                });
        },
        updateCurrentUploadedSize(event) {
            if (event.lengthComputable) {
                this.currentUploadedSize = event.loaded;
            }
        },
        finishSubmission() {
            return StorageRequestApi.update({id: this.storageRequest.id}, {})
                .then(() => this.finished = true);
        },
    },
    created() {
        this.maxSize = biigle.$require('user-storage.maxSize');
    },
};
</script>
