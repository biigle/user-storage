<script>
import FileBrowser from './components/fileBrowser';
import RequestApi from './api/storageRequests';
import {LoaderMixin, handleErrorResponse} from './import';
import {buildDirectoryTree} from './utils';

export default {
    mixins: [LoaderMixin],
    components: {
        fileBrowser: FileBrowser,
    },
    data() {
        return {
            request: null,
            rejecting: false,
            rejectReason: '',
            approved: false,
            rejected: false,
        };
    },
    computed: {
        requestRoot() {
            return buildDirectoryTree(this.request);
        },
        cannotReject() {
            return this.loading || !this.rejectReason;
        },
        finished() {
            return this.approved || this.rejected;
        },
    },
    methods: {
        handleApprove() {
            this.startLoading();
            RequestApi.approve({id: this.request.id}, {})
                .then(this.handleApproved, handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleApproved() {
            this.approved = true;
        },
        handleRejecting() {
            this.rejecting = true;
        },
        handleCancelReject() {
            this.rejecting = false;
            this.rejectReason = '';
        },
        handleReject() {
            this.startLoading();
            RequestApi.reject({id: this.request.id}, {reason: this.rejectReason})
                .then(this.handleRejected, handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleRejected() {
            this.rejected = true;
        },
    },
    created() {
        this.request = biigle.$require('user-storage.request');
    },
};
</script>
