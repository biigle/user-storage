/**
 * Resource for storage requests.
 *
 * Create a storage request:
 * resource.save().then(...)
 *
 * Submit a storage request:
 * resource.update({id: requestId}).then(...)
 *
 * Approve a storage request:
 * resource.approve({id: requestId}).then(...)
 *
 * Reject a storage request:
 * resource.reject({id: requestId}).then(...)
 *
 * Extend a storage request:
 * resource.extend({id: requestId}).then(...)
 *
 * Delete a storage request:
 * resource.delete({id: videoId}).then(...);
 *
 * @type {Vue.resource}
 */
export default Vue.resource('api/v1/storage-requests{/id}', {}, {
    approve: {
        method: 'POST',
        url: 'api/v1/storage-requests{/id}/approve',
    },
    reject: {
        method: 'POST',
        url: 'api/v1/storage-requests{/id}/reject',
    },
    extend: {
        method: 'POST',
        url: 'api/v1/storage-requests{/id}/extend',
    },
});
