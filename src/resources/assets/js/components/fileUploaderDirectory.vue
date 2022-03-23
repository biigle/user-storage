<template>
    <div
        class="storage-file-uploader-directory"
        :class="classObject"
        >
        <div class="storage-file-uploader-directory-name" @click="handleClick">
            <i class="fa fa-folder"></i> <span v-text="path"></span>
        </div>
        <ul v-if="hasItems" class="storage-file-uploader-directory-list">
            <li v-for="(dir, path) in directory.directories">
                <file-uploader-directory
                    :path="path"
                    :directory="dir"
                    @select="emitSelect"
                    @unselect="emitUnselect"
                    ></file-uploader-directory>
            </li>
            <li
                v-for="file in directory.files"
                class="storage-file-uploader-file"
                >
                <i class="fa fa-file"></i> <span v-text="file.name"></span>
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
    }
};
</script>
