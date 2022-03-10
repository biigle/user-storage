<?php

$router->group([
    'namespace' => 'Api',
    'prefix' => 'api/v1',
    'middleware' => ['api', 'auth:web,api'],
], function ($router) {
    $router->resource('storage-requests', 'StorageRequestController', [
        'only' => ['store', 'update', 'destroy'],
        'parameters' => ['storage-requests' => 'id'],
    ]);

    $router->post('storage-requests/{id}/files', 'StorageRequestController@storeFile');
});
