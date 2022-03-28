<script>
import DirectoriesApi from './api/storageRequestDirectories';
import FileBrowser from './components/fileBrowser';
import FilesApi from './api/storageRequestFiles';
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
            itemDeleted: false,
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
            this.itemDeleted = true;
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

            this.startLoading();
            // Remove the leading slash from the path.
            path = path.slice(1);
            DirectoriesApi.delete({id: this.selectedRequest.id}, {directories: [path]})
                .then(() => this.directoryRemoved(path), handleErrorResponse)
                .finally(this.finishLoading);
        },
        directoryRemoved(prefix) {
            this.selectedRequest.files = this.selectedRequest.files.filter(function (p) {
                return !p.startsWith(prefix + '/');
            });
            this.selectedRequest.files_count = this.selectedRequest.files.length;
            this.itemDeleted = true;
        },
        removeFile(file, path) {
            if (this.loading) {
                return;
            }

            this.startLoading();
            // Remove the leading slash from the path.
            path = `${path.slice(1)}/${file.name}`;
            FilesApi.delete({id: this.selectedRequest.id}, {files: [path]})
                .then(() => this.fileRemoved(path), handleErrorResponse)
                .finally(this.finishLoading);
        },
        fileRemoved(path) {
            let index = this.selectedRequest.files.indexOf(path);
            if (index !== -1) {
                this.selectedRequest.files.splice(index, 1);
                this.selectedRequest.files_count -= 1;
            }
            this.itemDeleted = true;
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
