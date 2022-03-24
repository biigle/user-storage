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

            <button
                class="btn btn-success pull-right"
                :disabled="hasNoFiles"
                >
                <i class="fa fa-upload"></i> Submit
            </button>
        </div>

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

export default {
    props: {
        accept: {
            type: String,
            default: '',
        },
    },
    components: {
        directory: Directory,
    },
    data() {
        return {
            rootDirectory: {
                name: name,
                directories: {},
                files: [],
                selected: false,
            },
            selectedDirectory: null,
            editable: true,
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
        hasNoFiles() {
            return true;
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
        },
    },
    mounted() {
        //
    },
};
</script>
