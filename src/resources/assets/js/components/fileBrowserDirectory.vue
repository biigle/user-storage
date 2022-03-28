<template>
    <div
        class="storage-file-browser-directory"
        :class="classObject"
        >
        <div
            v-if="!root"
            class="storage-file-browser-directory-name clearfix"
            @click="handleClick"
            >

            <i
                v-if="collapsed"
                class="fa fa-angle-right fa-fw collapse-caret"
                title="Expand this directory"
                @click.stop="handleUncollapse"
                ></i>
            <i
                v-else
                class="fa fa-angle-down fa-fw collapse-caret"
                title="Collapse this directory"
                @click.stop="handleCollapse"
                ></i>

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
        <ul
            v-show="!collapsed"
            class="storage-file-browser-directory-list"
            >
            <li v-for="(dir, path) in directory.directories">
                <file-browser-directory
                    :path="path"
                    :dirname="fullPath"
                    :directory="dir"
                    :removable="removable"
                    :selectable="selectable"
                    :download-url="downloadUrl"
                    @select="emitSelect"
                    @unselect="emitUnselect"
                    @remove-directory="emitRemoveDirectory"
                    @remove-file="emitRemoveFile"
                    ></file-browser-directory>
            </li>
            <li
                v-for="file in directory.files"
                class="storage-file-browser-file clearfix"
                >
                <a
                    v-if="downloadUrl"
                    :href="fullDownloadUrl(file.name)"
                    title="View file"
                    >
                    <i class="fa fa-file"></i> <span v-text="file.name"></span>
                </a>
                <span v-else>
                    <i class="fa fa-file"></i> <span v-text="file.name"></span>
                </span>

                <button
                    v-if="removable"
                    class="btn btn-default btn-xs pull-right"
                    title="Remove the file"
                    @click.stop="handleRemoveFile(file)"
                    >
                    <i class="fa fa-trash"></i>
                </button>
            </li>
            <li
                v-if="!hasItems"
                class="storage-file-browser-file text-muted"
                title="This directory is empty"
                >
                (empty)
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'file-browser-directory',
    props: {
        path: {
            type: String,
            default: '',
        },
        dirname: {
            type: String,
            default: '/',
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
        selectable: {
            type: Boolean,
            default: false,
        },
        downloadUrl: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            collapsed: false,
        };
    },
    computed: {
        classObject() {
            return {
                selected: this.directory.selected,
                root: this.root,
                selectable: this.selectable,
            };
        },
        hasItems() {
            return this.directory.files.length > 0 || Object.keys(this.directory.directories).length > 0;
        },
        fullPath() {
            if (this.dirname === '/') {
                return '/' + this.path;
            }

            return this.dirname + '/' + this.path;
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
            if (!this.selectable) {
                return;
            }

            if (this.directory.selected) {
                this.emitUnselect(this.directory);
            } else {
                this.emitSelect(this.directory);
            }
        },
        emitRemoveDirectory(directory, path) {
            this.$emit('remove-directory', directory, path);
        },
        handleRemoveDirectory() {
            if (this.removable) {
                this.emitRemoveDirectory(this.directory, this.fullPath);
            }
        },
        emitRemoveFile(file, path) {
            this.$emit('remove-file', file, path);
        },
        handleRemoveFile(file) {
            if (this.removable) {
                this.emitRemoveFile(file, this.fullPath);
            }
        },
        handleCollapse() {
            this.collapsed = true;
        },
        handleUncollapse() {
            this.collapsed = false;
        },
        fullDownloadUrl(filename) {
            // Remove leading slash.
            let path = this.fullPath.slice(1) + '/' + filename;

            return this.downloadUrl + '?path=' + encodeURIComponent(path);
        },
    },
    watch: {
        hasItems(hasItems) {
            if (!hasItems) {
                this.collapsed = false;
            }
        },
    },
};
</script>
