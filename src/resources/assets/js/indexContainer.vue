<script>
import RequestApi from './api/storageRequests';
import RequestList from './components/storageRequestList';
import {LoaderMixin, handleErrorResponse} from './import';
import {sizeForHumans} from './utils';

export default {
    mixins: [LoaderMixin],
    components: {
        requestList: RequestList,
    },
    data() {
        return {
            requests: [],
            expireDate: 0,
            usedQuotaBytes: 0,
            availableQuotaBytes: 0,
        };
    },
    computed: {
        usedQuota() {
            return sizeForHumans(this.usedQuotaBytes);
        },
        availableQuota() {
            return sizeForHumans(this.availableQuotaBytes);
        },
        usedQuotaPercent() {
            return Math.round(this.usedQuotaBytes / this.availableQuotaBytes * 100);
        },
    },
    methods: {
        handleSelect(request) {
            //
        },
        handleDelete(request) {
            if (!confirm('Do you really want to delete the storage request with all files?')) {
                return;
            }
            this.startLoading();
            RequestApi.delete({id: request.id}, {})
                .then(() => this.handleDeleted(request), handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleDeleted(request) {
            let index = this.requests.indexOf(request);
            if (index !== -1) {
                this.requests.splice(index, 1);
            }
        },
        handleExtend(request) {
            this.startLoading();
            RequestApi.extend({id: request.id}, {})
                .then(this.handleExtended, handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleExtended(response) {
            let request = this.requests.find((r) => r.id === response.body.id);
            request.expires_at = response.body.expires_at;
            request.expires_at_for_humans = response.body.expires_at_for_humans;
        },
    },
    created() {
        this.requests = biigle.$require('user-storage.requests');
        this.expireDate = new Date(biigle.$require('user-storage.expireDate'));
        this.usedQuotaBytes = biigle.$require('user-storage.usedQuota');
        this.availableQuotaBytes = biigle.$require('user-storage.availableQuota');
    },
};
</script>
