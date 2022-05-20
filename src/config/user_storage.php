<?php

return [
    /*
    | Number of pending (i.e. unconfirmed) storage requests each user is allowed o create.
    */
    'max_pending_requests' => env('USER_STORAGE_MAX_PENDING_REQUESTS', 3),

    /*
    | Maximum allowed size of a single uploaded file in bytes.
    |
    | Default: 5 GB
    */
    'max_file_size' => env('USER_STORAGE_MAX_FILE_SIZE', 5E+9),

    /*
    | Allowed maximum combined file size for storage per user (in bytes).
    |
    | Default: 10 GB
    */
    'user_quota' => env('USER_STORAGE_USER_QUOTA', 1E+10),

    /*
    | Name of the storage disk to use for (approved) user storage files.
    */
    'storage_disk' => env('USER_STORAGE_STORAGE_DISK'),

    /*
    | Name of the storage disk to use for pending storage requests.
    | This can be the same than storage_disk.
    */
    'pending_disk' => env('USER_STORAGE_PENDING_DISK'),

    /*
    | Number of months until a confirmed storage request expires.
    */
    'expires_months' => env('USER_STORAGE_EXPIRES_MONTHS', 12),

    /*
    | Number of weeks before expiration when a storage request is classified as "about
    | to expire".
    */
    'about_to_expire_weeks' => env('USER_STORAGE_ABOUT_TO_EXPIRE_WEEKS', 4),

    /*
    | Number of weeks to wait after expiration before a storage request is actually
    | deleted. This is also used to prune stale storage requests, i.e. requests that have
    | been created but were not submitted, yet.
    */
    'delete_grace_period_weeks' => env('USER_STORAGE_DELETE_GRACE_PERIOD_WEEKS', 1),

    /*
     | Enable to disallow creation of new storage requests and upload of files.
     | Global admins are exempted.
     */
    'maintenance_mode' => env('USER_STORAGE_MAINTENANCE_MODE', false),

    /*
    | Split files that are larger than this threshold (in bytes) into smaller chunks.
    | Each chunk will have the size of this threshold (except maybe the last). Larger
    | single file uploads are rejected.
    |
    | Default: 100 MB.
    */
    'upload_chunk_size' => env('USER_STORAGE_UPLOAD_CHUNK_SIZE', 1E+8),

    /*
    | Directory where the temporary files to assemble chunked files are stored.
    */
    'tmp_dir' => env('USER_STORAGE_TMP_DIR', storage_path('user-storage-tmp')),

    'notifications' => [
        /*
        | Set the way notifications for storage requests are sent by default.
        |
        | Available are: "email", "web"
        */
        'default_settings' => 'email',

        /*
        | Choose whether users are allowed to change their notification settings.
        | If set to false the default settings will be used for all users.
        */
        'allow_user_settings' => true,
    ],
];
