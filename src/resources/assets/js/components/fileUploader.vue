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
                @click="addFiles"
                >
                <i class="fa fa-file"></i> Add files
            </button>

            <button
                class="btn btn-default"
                @click="addDirectory"
                >
                <i class="fa fa-folder"></i> Add directory
            </button>

            <button
                class="btn btn-success pull-right"
                :disabled="hasNoFiles"
                >
                <i class="fa fa-upload"></i> Submit
            </button>
        </div>

        <p v-if="hasSelectedDirectory" class="text-muted">
            New files and directories will be added to the selected directory <span v-text="selectedDirectoryName"></span>.
        </p>
        <p v-else class="text-muted">
            New files and directories will be added to the top-level directory.
        </p>


        <div v-show="hasItems" class="panel panel-default">
            <directory
                path="/"
                :directory="rootDirectory"
                :root="true"
                @select="selectDirectory"
                @unselect="unselectDirectory"
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
            return this.rootDirectory.files.length > 0 || Object.keys(this.rootDirectory.directories).length > 0;
        },
        hasNoFiles() {
            return true;
        },
    },
    methods: {
        handleFilesChosen(event) {
            let newFiles = event.target.files;
            let files = this.rootDirectory.files;
            let i = 0;

            if (this.hasSelectedDirectory) {
                files = this.selectedDirectory.files;
            }

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
            let directories = this.rootDirectory.directories;
            if (this.hasSelectedDirectory) {
                directories = this.selectedDirectory.directories;
            }

            path.split('/').forEach(function (name) {
                if (!directories.hasOwnProperty(name)) {
                    Vue.set(directories, name, {
                        name: name,
                        directories: {},
                        files: [],
                        selected: false,
                    });
                }

                directories = directories[name].directories;
            });
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
    },
    mounted() {
        //
    },
};
</script>
