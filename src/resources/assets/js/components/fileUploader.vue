<template>
    <div class="storage-file-uploader well well-sm">
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
                <span v-if="hasSelectedDirectory">
                    <i class="fa fa-file"></i> Add files to <span v-text="selectedDirectoryName"></span>
                </span>
                <span v-else>
                    <i class="fa fa-file"></i> Add files
                </span>
            </button>

            <button
                class="btn btn-default"
                @click="addDirectory"
                >
                <span v-if="hasSelectedDirectory">
                    <i class="fa fa-folder"></i> Add directory to <span v-text="selectedDirectoryName"></span>
                </span>
                <span v-else>
                    <i class="fa fa-folder"></i> Add directory
                </span>
            </button>

            <button
                class="btn btn-success pull-right"
                :disabled="hasNoFiles"
                >
                <i class="fa fa-upload"></i> Submit
            </button>
        </div>

        <directory
            v-for="(dir, path) in rootDirectories"
            :key="path"
            :path="path"
            :directory="dir"
            @select="selectDirectory"
            @unselect="unselectDirectory"
            ></directory>
        <ul class="storage-file-uploader-directory-list">
            <li
                v-for="file in rootFiles"
                class="storage-file-uploader-file"
                >
                <i class="fa fa-file"></i> <span v-text="file.name"></span>
            </li>
        </ul>
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
            rootDirectories: {},
            rootFiles: [],
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
        hasNoFiles() {
            return true;
        },
    },
    methods: {
        handleFilesChosen(event) {
            let newFiles = event.target.files;
            let directories = this.rootDirectories;
            let files = this.rootFiles;
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
            let directories = this.rootDirectories;
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
