import {Resource} from '../import.js';

/**
 * Resource for storage request files.
 *
 * Upload a file:
 * resource.save({id: requestId}, {file: File, prefix: 'xxx'}).then(...)
 *
 * Delete a file:
 * resource.delete({id: fileId}).then(...)
 */
export default Resource('api/v1/storage-request-files{/id}', {}, {
    save: {
        method: 'POST',
        url: 'api/v1/storage-requests{/id}/files',
    },
});
