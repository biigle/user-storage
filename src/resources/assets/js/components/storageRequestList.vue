<template>
<div class="list-group storage-request-list">
    <list-item
        v-for="request in requests"
        :key="request.id"
        :request="request"
        :expire-date="expireDate"
        :selected="isSelectedRequest(request)"
        @select="emitSelect"
        @delete="emitDelete"
        @extend="emitExtend"
        ></list-item>
    <span
        v-if="noItems"
        class="list-group-item text-muted"
        >
        no storage requests
    </span>
</div>
</template>

<script>
import ListItem from './storageRequestListItem';

export default {
    components: {
        listItem: ListItem,
    },
    props: {
        requests: {
            type: Array,
            required: true,
        },
        expireDate: {
            type: Date,
            default: null
        },
        selectedRequest: {
            type: Object,
            default: null,
        },
    },
    computed: {
        noItems() {
            return this.requests.length === 0;
        },
    },
    methods: {
        emitDelete(request) {
            this.$emit('delete', request);
        },
        emitExtend(request) {
            this.$emit('extend', request);
        },
        emitSelect(request) {
            this.$emit('select', request);
        },
        isSelectedRequest(request) {
            return this.selectedRequest ? this.selectedRequest.id === request.id : false;
        },
    },
};
</script>
