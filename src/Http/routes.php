<?php

$router->group([
    'namespace' => 'Api',
    'prefix' => 'api/v1',
    'middleware' => ['api', 'auth:web,api'],
], function ($router) {
    $router->resource('storage-requests', 'StorageRequestController', [
        'only' => ['show', 'store', 'update', 'destroy'],
        'parameters' => ['storage-requests' => 'id'],
    ]);

    $router->post('storage-requests/{id}/extend', 'StorageRequestController@extend');

    $router->resource('storage-requests.files', 'StorageRequestFileController', [
        'only' => ['store'],
        'parameters' => ['storage-requests' => 'id'],
    ]);

    $router->get('storage-request-files/{id}', 'StorageRequestFileController@show');

    $router->delete('storage-request-files/{id}', 'StorageRequestFileController@destroy');
    $router->delete('storage-requests/{id}/directories', 'StorageRequestDirectoryController@destroy');

    $router->group([
        'middleware' => ['can:review'],
    ], function ($router) {
        $router->post('storage-requests/{id}/approve', 'StorageRequestController@approve');
        $router->post('storage-requests/{id}/reject', 'StorageRequestController@reject');
    });

    $router->group([
        'middleware' => ['can:sudo'],
    ], function ($router) {
        $router->post('users/{id}/storage-request-quota', 'StorageRequestUserController@store');
    });

});

$router->group([
    'namespace' => 'Views',
    'middleware' => ['web', 'auth'],
], function ($router) {
    $router->get('storage-requests/create', [
        'as' => 'create-storage-requests',
        'uses' => 'StorageRequestController@create',
    ]);

    $router->get('storage-requests', [
        'as' => 'index-storage-requests',
        'uses' => 'StorageRequestController@index',
    ]);

    $router->get('storage-requests/{id}/review', [
        'as' => 'review-storage-request',
        'uses' => 'StorageRequestController@review',
    ]);
});
