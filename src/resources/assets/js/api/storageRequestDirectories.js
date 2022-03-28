/**
 * Resource for storage request directories.
 *
 * Delete directories:
 * resource.delete({id: requestId}, {directories: directoryPathsArray}).then(...)
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/storage-requests{/id}/directories');
