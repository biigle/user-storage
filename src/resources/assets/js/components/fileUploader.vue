<template>
    <div class="storage-file-uploader">
        <input
            ref="fileInput"
            class="hidden"
            type="file"
            multiple
            :accept="accept"
            @input="handleFilesChosen"
            >

        <div class="storage-file-uploader-buttons">
            <div v-if="loading" class="text-info">
                <loader :active="true"></loader>
                Uploaded <span v-text="uploadedSizeForHumans"></span> of <span v-text="totalSizeForHumans"></span>
                (<span v-text="uploadedPercent"></span>%)
            </div>
            <div v-else>
                <button
                    class="btn btn-default"
                    title="Add a new directory"
                    @click="addDirectory"
                    >
                    <i class="fa fa-folder"></i> Add directory
                </button>

                <button
                    v-if="hasSelectedDirectory"
                    class="btn btn-default"
                    title="Add new files"
                    @click="addFiles"
                    >
                    <i class="fa fa-file"></i> Add files
                </button>
                <button
                    v-else
                    class="btn btn-default"
                    title="Please create or select a directory to add files to"
                    disabled
                    >
                    <i class="fa fa-file"></i> Add files
                </button>

                <span class="pull-right">
                    <button
                        v-if="hasFiles"
                        title="Submit the storage request and upload the files"
                        class="btn btn-success"
                        @click="handleSubmit"
                        :disabled="exceedsMaxSize"
                        >
                        <i class="fa fa-upload"></i> Submit
                        <span v-text="totalSizeForHumans"></span>
                    </button>
                    <button
                        v-else
                        class="btn btn-success"
                        title="Add files to submit in this storage request"
                        disabled
                        >
                        <i class="fa fa-upload"></i> Submit
                    </button>
                </span>
            </div>
        </div>

        <p v-if="exceedsMaxSize" class="text-danger">
            You have selected more than the <span v-text="maxSizeForHumans"></span> of storage available to you.
        </p>

        <div v-show="hasItems" class="panel panel-default">
            <directory
                path="/"
                :directory="rootDirectory"
                :root="true"
                :removable="editable"
                @select="selectDirectory"
                @unselect="unselectDirectory"
                @remove-directory="removeDirectory"
                @remove-file="removeFile"
                ></directory>
        </div>
    </div>
</template>

<script>
import Directory from './fileUploaderDirectory';
import FileApi from '../api/storageRequestFiles';
import StorageRequestApi from '../api/storageRequests';
import {LoaderMixin} from '../import';

export default {
    mixins: [LoaderMixin],
    props: {
        accept: {
            type: String,
            default: '',
        },
        maxSize: {
            type: Number,
            default: -1,
        },
    },
    components: {
        directory: Directory,
    },
    data() {
        return {
            files: [],
            rootDirectory: {
                name: '',
                directories: {},
                files: [],
                selected: false,
            },
            selectedDirectory: null,
            uploadedSize: 0,
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
        hasItems() {
            return Object.keys(this.rootDirectory.directories).length > 0;
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
            return this.sizeForHumans(this.totalSize);
        },
        uploadedPercent() {
            return (this.uploadedSize / this.totalSize * 100).toFixed(2);
        },
        uploadedSizeForHumans() {
            return this.sizeForHumans(this.uploadedSize);
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
            return this.sizeForHumans(this.maxSize);
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
            if (directory.selected) {
                this.selectedDirectory = null;
            }

            this.syncFiles();
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
        },
        sizeForHumans(size) {
            let unit = '';
            let units = ['kB', 'MB', 'GB', 'TB'];
            do {
                size /= 1000;
                unit = units.shift();
            } while (size > 1000 && units.length > 0);

            return `${size.toFixed(2)} ${unit}`;
        },
    },

};
</script>
