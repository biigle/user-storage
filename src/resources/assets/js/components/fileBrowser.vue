<template>
    <div class="storage-file-browser">
        <div v-show="hasItems" class="panel panel-default">
            <directory
                path="/"
                :directory="rootDirectory"
                :root="true"
                :removable="editable"
                @select="emitSelect"
                @unselect="emitUnselect"
                @remove-directory="emitRemoveDirectory"
                @remove-file="emitRemoveFile"
                ></directory>
        </div>
    </div>
</template>

<script>
import Directory from './fileBrowserDirectory';

export default {
    components: {
        directory: Directory,
    },
    props: {
        rootDirectory: {
            type: Object,
            required: true,
        },
        editable: {
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
        hasItems() {
            return Object.keys(this.rootDirectory.directories).length > 0;
        },
    },
    methods: {
        emitSelect(directory) {
            this.$emit('select', directory);
        },
        emitUnselect(directory) {
            this.$emit('unselect', directory);
        },
        emitRemoveDirectory(directory, path) {
            this.$emit('remove-directory', directory, path);
        },
        emitRemoveFile(file, path) {
            this.$emit('remove-file', file, path);
        },
    },
};
</script>
