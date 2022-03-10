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

    $router->resource('storage-requests.files', 'StorageRequestFileController', [
        'only' => ['store', 'destroy'],
        'parameters' => ['storage-requests' => 'id'],
    ]);

    $router->group([
        'middleware' => ['can:sudo'],
    ], function ($router) {
        $router->post('storage-requests/{id}/confirm', 'StorageRequestController@confirm');
    });

});
