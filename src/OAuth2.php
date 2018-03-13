<?php
namespace Batmahir\OAuth2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class OAuth2
{
    public static function route()
    {
        Route::get('authorize/{client_id}/{client_secret}', [
            'as' => 'oauth2-authorize',
            'uses' => '\Batmahir\OAuth2\OAuth2@authorize_client',
        ]);

        Route::get('direct-authorize', [
            'as' => 'oauth2-direct-authorize',
            'uses' => '\Batmahir\OAuth2\OAuth2@directAuthorize',
        ]);
    }

    public function authorize_client($client_id , $client_secret)
    {
        dd('here');
    }

    public function directAuthorize(Request $request)
    {

    }
}