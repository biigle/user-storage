/**
 * Resource for storage request files.
 *
 * Upload a file:
 * resource.save({id: requestId}, {file: File, prefix: 'xxx'}).then(...)
 *
 * Delete files:
 * resource.delete({id: requestId}, {files: filePathsArray}).then(...)
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/storage-requests{/id}/files');