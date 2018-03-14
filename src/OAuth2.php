<?php
namespace Batmahir\OAuth2;

use App\Models\OAuthAccessToken;
use App\Models\User;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use \Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;

class OAuth2 extends AccessTokenController
{
    protected $authenticated_user_id;

    protected $authenticated_user_email;

    protected $authenticated_user_name;


    public static function route()
    {
        Route::get('authorize/{client_id}/{client_secret}', [
            'as' => 'oauth2-authorize',
            'uses' => '\Batmahir\OAuth2\OAuth2@authorize_client',
        ]);

        Route::post('direct-authorize', [
            'as' => 'oauth2-direct-authorize',
            'uses' => '\Batmahir\OAuth2\OAuth2@directAuthorize',
        ]);
    }

    public function authorize_client($client_id , $client_secret)
    {

    }

    public function directAuthorize(ServerRequestInterface $request)
    {
        $this->checkUser($request->getParsedBody()['username'],$request->getParsedBody()['password']);
        $first_time_authorized = $this->checkClient($request);

        //if($first_time_authorized == false)
        //{
            $data =  $this->withErrorHandling(function () use ($request) {
                return $this->convertResponse(
                    $this->server->respondToAccessTokenRequest($request, new Psr7Response)
                );
            });
            dd($data->content());
        //}



    }

    public function checkUser($email,$password)
    {
        $user_collection = User::where('email','=',$email)->first();
        if(!isset($user_collection))
            throw new \Exception('Unauthorized custom');

        $user_auth = \Illuminate\Foundation\Auth\User::find($user_collection->id);

        $auth = (new BcryptHasher)->check($password,$user_auth->getAuthPassword());

        if($auth == false)
            throw new \Exception('Unauthorized custom');

        $this->authenticated_user_email = $user_collection->email;
        $this->authenticated_user_id = $user_collection->id;
        $this->authenticated_user_name = $user_collection->name;
        dd('here');
        return true;
    }

    public function checkClient(ServerRequestInterface $request)
    {
        $check_access_token =
        OAuthAccessToken::join('oauth_clients','oauth_access_tokens.client_id','=','oauth_clients.id')
            ->join('users','oauth_access_tokens.user_id','=','users.id')
            ->where('oauth_clients.id','=',$request->getParsedBody()['client_id'])
            ->where('oauth_clients.secret','=',$request->getParsedBody()['client_secret'])
            ->where('users.id','=',$this->authenticated_user_id)
            ->first();

        if(!isset($check_access_token ))
            return false;

        return true;
    }
}