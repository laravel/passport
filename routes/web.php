<?php

use Illuminate\Support\Facades\Route;

Route::post('/token', [
    'uses' => 'AccessTokenController@issueToken',
    'as' => 'token',
    'middleware' => 'throttle',
]);

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/token/refresh', [
        'uses' => 'TransientTokenController@refresh',
        'as' => 'token.refresh',
    ]);

    Route::get('/authorize', [
        'uses' => 'AuthorizationController@authorize',
        'as' => 'authorizations.authorize',
    ]);

    Route::post('/authorize', [
        'uses' => 'ApproveAuthorizationController@approve',
        'as' => 'authorizations.approve',
    ]);

    Route::delete('/authorize', [
        'uses' => 'DenyAuthorizationController@deny',
        'as' => 'authorizations.deny',
    ]);

    Route::get('/tokens', [
        'uses' => 'AuthorizedAccessTokenController@forUser',
        'as' => 'tokens.index',
    ]);

    Route::delete('/tokens/{token_id}', [
        'uses' => 'AuthorizedAccessTokenController@destroy',
        'as' => 'tokens.destroy',
    ]);

    Route::get('/clients', [
        'uses' => 'ClientController@forUser',
        'as' => 'clients.index',
    ]);

    Route::post('/clients', [
        'uses' => 'ClientController@store',
        'as' => 'clients.store',
    ]);

    Route::put('/clients/{client_id}', [
        'uses' => 'ClientController@update',
        'as' => 'clients.update',
    ]);

    Route::delete('/clients/{client_id}', [
        'uses' => 'ClientController@destroy',
        'as' => 'clients.destroy',
    ]);

    Route::get('/scopes', [
        'uses' => 'ScopeController@all',
        'as' => 'scopes.index',
    ]);

    Route::get('/personal-access-tokens', [
        'uses' => 'PersonalAccessTokenController@forUser',
        'as' => 'personal.tokens.index',
    ]);

    Route::post('/personal-access-tokens', [
        'uses' => 'PersonalAccessTokenController@store',
        'as' => 'personal.tokens.store',
    ]);

    Route::delete('/personal-access-tokens/{token_id}', [
        'uses' => 'PersonalAccessTokenController@destroy',
        'as' => 'personal.tokens.destroy',
    ]);
});
