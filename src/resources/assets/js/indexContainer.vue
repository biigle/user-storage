<script>
import FileBrowser from './components/fileBrowser';
import RequestApi from './api/storageRequests';
import RequestList from './components/storageRequestList';
import {LoaderMixin, handleErrorResponse} from './import';
import {sizeForHumans} from './utils';

export default {
    mixins: [LoaderMixin],
    components: {
        requestList: RequestList,
        fileBrowser: FileBrowser,
    },
    data() {
        return {
            requests: [],
            expireDate: 0,
            usedQuotaBytes: 0,
            availableQuotaBytes: 0,
            selectedRequest: null,
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
        hasSelectedRequest() {
            return this.selectedRequest !== null;
        },
        selectedRequestRoot() {
            let root = {
                name: '',
                directories: {},
                files: [],
            };

            if (this.selectedRequest.files) {
                this.selectedRequest.files.forEach(function (path) {
                    let breadcrumbs = path.split('/');
                    let file = breadcrumbs.pop();
                    let currentDir = root;
                    breadcrumbs.forEach(function (name) {
                        if (!currentDir.directories.hasOwnProperty(name)) {
                            currentDir.directories[name] = {
                                name: name,
                                directories: {},
                                files: [],
                            };
                        }

                        currentDir = currentDir.directories[name];
                    });

                    currentDir.files.push({name: file});
                });
            }

            return root;
        },
    },
    methods: {
        handleSelect(request) {
            if (request.files) {
                this.selectedRequest = request;

                return;
            }

            this.startLoading();
            RequestApi.get({id: request.id})
                .then(this.setFilesAndSelectRequest, handleErrorResponse)
                .finally(this.finishLoading);
        },
        setFilesAndSelectRequest(response) {
            let request = this.requests.find((r) => r.id === response.body.id);
            request.files = response.body.files;
            request.files_count = response.body.files_count;

            this.selectedRequest = request;
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

            if (this.selectedRequest && this.selectedRequest.id === request.id) {
                this.selectedRequest = null;
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
        removeDirectory(directory, path) {
            console.log(directory, path);
        },
        removeFile(file, path) {
            console.log(file, path);
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
