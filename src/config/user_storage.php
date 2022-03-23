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
    | deleted.
    */
    'delete_grace_period_weeks' => env('USER_STORAGE_DELETE_GRACE_PERIOD_WEEKS', 2),

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
