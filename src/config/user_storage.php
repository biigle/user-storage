<?php

return [
    /*
    | Number of pending (i.e. unconfirmed) storage requests each user is allowed o create.
    */
    'max_pending_requests' => env('USER_STORAGE_MAX_PENDING_REQUESTS', 3),
];
