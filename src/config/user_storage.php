<?php

return [
    /*
    | Number of pending (i.e. unconfirmed) storage requests each user is allowed o create.
    */
    'max_pending_requests' => env('USER_STORAGE_MAX_PENDING_REQUESTS', 3),

    /*
    | Allowed maximum combined file size for storage per user (in bytes).
    |
    | Default: 10 GB
    */
    'user_quota' => env('USER_STORAGE_USER_QUOTA', 10737418240),

    /*
    | Name of the storage disk to use for (approved) user storage files.
    */
    'storage_disk' => env('USER_STORAGE_STORAGE_DISK'),

    /*
    | Name of the storage disk to use for pending storage requests.
    | This can be the same than storage_disk.
    */
    'pending_disk' => env('USER_STORAGE_PENDING_DISK'),
];
