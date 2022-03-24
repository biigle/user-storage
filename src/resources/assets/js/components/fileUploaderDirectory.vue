<template>
    <div
        class="storage-file-uploader-directory"
        :class="classObject"
        >
        <div
            v-if="!root"
            class="storage-file-uploader-directory-name clearfix"
            @click="handleClick"
            >
            <i class="fa fa-folder"></i> <span v-text="path"></span>

            <button
                v-if="removable"
                class="btn btn-default btn-xs pull-right"
                title="Remove the directory"
                @click.stop="handleRemoveDirectory"
                >
                <i class="fa fa-trash"></i>
            </button>
        </div>
        <ul v-if="hasItems" class="storage-file-uploader-directory-list">
            <li v-for="(dir, path) in directory.directories">
                <file-uploader-directory
                    :path="path"
                    :directory="dir"
                    :removable="removable"
                    @select="emitSelect"
                    @unselect="emitUnselect"
                    @remove-directory="emitRemoveDirectory"
                    @remove-file="emitRemoveFile"
                    ></file-uploader-directory>
            </li>
            <li
                v-for="file in directory.files"
                class="storage-file-uploader-file clearfix"
                >
                <i class="fa fa-file"></i> <span v-text="file.name"></span>

                <button
                    class="btn btn-default btn-xs pull-right"
                    title="Remove the file"
                    @click.stop="handleRemoveFile(file)"
                    >
                    <i class="fa fa-trash"></i>
                </button>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'file-uploader-directory',
    props: {
        path: {
            type: String,
            required: true,
        },
        directory: {
            type: Object,
            required: true,
        },
        root: {
            type: Boolean,
            default: false,
        },
        removable: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            //
        };
    },
    computed: {
        classObject() {
            return {
                selected: this.directory.selected,
                root: this.root,
            };
        },
        hasItems() {
            return this.directory.files.length > 0 || Object.keys(this.directory.directories).length > 0;
        },
    },
    methods: {
        emitSelect(directory) {
            this.$emit('select', directory);
        },
        emitUnselect(directory) {
            this.$emit('unselect', directory);
        },
        handleClick() {
            if (this.directory.selected) {
                this.emitUnselect(this.directory);
            } else {
                this.emitSelect(this.directory);
            }
        },
        emitRemoveDirectory(directory, path) {
            if (!path) {
                path = this.path;
            } else {
                path = this.root ? `/${path}` : `${this.path}/${path}`;
            }

            this.$emit('remove-directory', directory, path);
        },
        handleRemoveDirectory() {
            this.emitRemoveDirectory(this.directory);
        },
        emitRemoveFile(file, path) {
            if (!path) {
                path = this.path;
            } else {
                path = this.root ? `/${path}` : `${this.path}/${path}`;
            }

            this.$emit('remove-file', file, path);
        },
        handleRemoveFile(file) {
            if (this.removable) {
                this.emitRemoveFile(file);
            }
        },
    },
};
</script>
