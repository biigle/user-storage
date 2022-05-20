<template>
<a
    href="#"
    class="list-group-item"
    :class="classObject"
    @click.prevent="handleSelect"
    >
    #<span v-text="request.id"></span> created <span v-text="request.created_at_for_humans"></span> with <span v-text="request.files_count"></span> files (<span v-text="sizeForHumans"></span>).
    <span v-if="pending">
        <span class="label label-default" title="Waiting for review">pending</span>
    </span>
    <span v-else>
        <span v-if="expired">
            <span class="label label-danger" title="The storage request is expired and will be deleted soon">expired</span>
        </span>
        <span v-else>
            <span
                v-if="expiresSoon"
                class="label label-warning"
                :title="expiresTitle"
                >
                expires <span v-text="this.request.expires_at_for_humans"></span>
            </span>
            <span
                v-else
                class="label label-success"
                :title="expiresTitle"
                >
                approved
            </span>
        </span>
    </span>
    <span class="pull-right">
        <button
            class="btn btn-default btn-xs delete-button"
            title="Delete this storage request"
            @click.prevent="handleDelete"
            >
            <i class="fa fa-trash"></i>
        </button>
        <button
            v-if="expiresSoon"
            class="btn btn-default btn-xs"
            title="Extend this storage request"
            @click.prevent="handleExtend"
            >
            <i class="fa fa-redo"></i>
        </button>
    </span>
</a>
</template>

<script>
import {sizeForHumans} from './../utils';

export default {
    props: {
        request: {
            type: Object,
            required: true,
        },
        expireDate: {
            type: Date,
            default: null,
        },
        selected: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        pending() {
            return !this.request.expires_at;
        },
        expired() {
            // This is also false if expires_at is not set.
            return Date.parse(this.request.expires_at) < Date.now();
        },
        expiresSoon() {
            if (this.request.expires_at && this.expireDate) {
                return Date.parse(this.request.expires_at) < this.expireDate.getTime();
            }

            return false;
        },
        expiresTitle() {
            return `Expires ${this.request.expires_at_for_humans}`;
        },
        filesCount() {
            if (this.request.files) {
                return this.request.files.length;
            }

            return this.request.files_count || 0;
        },
        classObject() {
            return {
                active: this.selected,
            };
        },
        sizeForHumans() {
            return sizeForHumans(this.request.size);
        },
    },
    methods: {
        handleSelect() {
            this.$emit('select', this.request);
        },
        handleDelete() {
            this.$emit('delete', this.request);
        },
        handleExtend() {
            if (this.expiresSoon) {
                this.$emit('extend', this.request);
            }
        },
    },
};
</script>
