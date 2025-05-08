import {Resource} from '../import.js';

/**
 * Resource for storage request directories.
 *
 * Delete directories:
 * resource.delete({id: requestId}, {directories: directoryPathsArray}).then(...)
 */
export default Resource('api/v1/storage-requests{/id}/directories');
