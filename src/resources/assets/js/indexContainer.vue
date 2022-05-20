<script>
import DirectoriesApi from './api/storageRequestDirectories';
import FilesApi from './api/storageRequestFiles';
import RequestApi from './api/storageRequests';
import RequestList from './components/storageRequestList';
import {LoaderMixin, handleErrorResponse, FileBrowserComponent} from './import';
import {buildDirectoryTree, sizeForHumans} from './utils';

export default {
    mixins: [LoaderMixin],
    components: {
        requestList: RequestList,
        fileBrowser: FileBrowserComponent,
    },
    data() {
        return {
            requests: [],
            expireDate: 0,
            availableQuotaBytes: 0,
            selectedRequest: null,
        };
    },
    computed: {
        totalRequestSize() {
            return this.requests.reduce(function (acc, request) {
                return acc + request.size;
            }, 0);
        },
        usedQuota() {
            return sizeForHumans(this.totalRequestSize);
        },
        availableQuota() {
            return sizeForHumans(this.availableQuotaBytes);
        },
        usedQuotaPercent() {
            return Math.round(this.totalRequestSize / this.availableQuotaBytes * 100);
        },
        hasSelectedRequest() {
            return this.selectedRequest !== null;
        },
        selectedRequestRoot() {
            return buildDirectoryTree(this.selectedRequest);
        },
    },
    methods: {
        handleSelectRequest(request) {
            if (this.loading) {
                return;
            }

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
            Vue.set(request, 'files', response.body.files);
            request.files_count = response.body.files_count;

            this.selectedRequest = request;
        },
        handleDeleteRequest(request) {
            if (this.loading) {
                return;
            }

            if (!confirm('Do you really want to delete the storage request with all files?')) {
                return;
            }
            this.startLoading();
            RequestApi.delete({id: request.id}, {})
                .then(() => this.handleRequestDeleted(request), handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleRequestDeleted(request) {
            let index = this.requests.indexOf(request);
            if (index !== -1) {
                this.requests.splice(index, 1);
            }

            if (this.selectedRequest && this.selectedRequest.id === request.id) {
                this.selectedRequest = null;
            }
        },
        handleExtendRequest(request) {
            if (this.loading) {
                return;
            }

            this.startLoading();
            RequestApi.extend({id: request.id}, {})
                .then(this.handleRequestExtended, handleErrorResponse)
                .finally(this.finishLoading);
        },
        handleRequestExtended(response) {
            let request = this.requests.find((r) => r.id === response.body.id);
            request.expires_at = response.body.expires_at;
            request.expires_at_for_humans = response.body.expires_at_for_humans;
        },
        removeDirectory(directory, path) {
            if (this.loading) {
                return;
            }

            if (!confirm('Do you really want to delete the directory with all files?')) {
                return;
            }

            this.startLoading();
            // Remove the leading slash from the path.
            path = path.slice(1);
            DirectoriesApi.delete({id: this.selectedRequest.id}, {directories: [path]})
                .then(() => this.directoryRemoved(path), handleErrorResponse)
                .finally(this.finishLoading);
        },
        directoryRemoved(prefix) {
            let request = this.selectedRequest;
            request.files = request.files.filter(function (file) {
                return !file.path.startsWith(prefix + '/');
            });
            this.refreshRequestFileCountAndSize(request);
        },
        removeFile(file) {
            if (this.loading) {
                return;
            }

            if (!confirm('Do you really want to delete the file?')) {
                return;
            }

            this.startLoading();
            FilesApi.delete({id: file.id})
                .then(() => this.fileRemoved(file), handleErrorResponse)
                .finally(this.finishLoading);
        },
        fileRemoved(file) {
            let request = this.selectedRequest;
            request.files = request.files.filter(function (f) {
                return f.id !== file.id;
            });

            this.refreshRequestFileCountAndSize(request);
        },
        refreshRequestFileCountAndSize(request) {
            request.files_count = request.files.length;
            request.size = request.files.reduce(function (acc, file) {
                return acc + file.size;
            }, 0);
        },
    },
    created() {
        this.requests = biigle.$require('user-storage.requests');
        this.expireDate = new Date(biigle.$require('user-storage.expireDate'));
        this.availableQuotaBytes = biigle.$require('user-storage.availableQuota');
    },
};
</script>
