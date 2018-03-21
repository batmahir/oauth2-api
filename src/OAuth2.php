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

    public static function authorizedRoute()
    {
    }

    public function authorize_client($client_id , $client_secret)
    {

    }

    public function directAuthorize(ServerRequestInterface $request)
    {
        $checkUser = $this->checkUser($request->getParsedBody()['username'],$request->getParsedBody()['password']);

        if($checkUser == false)
        {
            return \response()->json(['error' => 'These credentials do not match our records.'],401);
        }

        $at_least_one_authorize = $this->checkClient($request); // this will check whether the user get authorize before or not
        $request2 = \request();

        if(!isset($at_least_one_authorize)) // create the token for the first time authenticate
        {
            $data =  $this->withErrorHandling(function () use ($request) {
                return $this->convertResponse(
                    $this->server->respondToAccessTokenRequest($request, new Psr7Response)
                );
            });

            $response = \json_decode($data->content());
            if(isset($response->error))
            {
                return \response()->json($response,200);
            }
            $request2->headers->set('authorization', $response->token_type.' '.$response->access_token);

            $token = Token::createTokenGuardObject()->validateToken($request2);
            $oauth_access_token_obj = OAuthAccessToken::find($token->oauth_access_token_id);
            $oauth_access_token_obj->access_token = $response->access_token;
            $oauth_access_token_obj->refresh_token = $response->refresh_token;
            $oauth_access_token_obj->token_type = $response->token_type;
            $oauth_access_token_obj->expires_in = $response->expires_in;
            $oauth_access_token_obj->save();

            $response =
                [
                    'token_type' => $response->token_type,
                    'expires_in' => $response->expires_in ,
                    'access_token' => $response->access_token,
                    'refresh_token' => $response->refresh_token,
                    'first_time_authenticate' => true
                ];

            return \response()->json($response,200);

        }

        $response =
        [
            'token_type' => $at_least_one_authorize->token_type,
            'expires_in' => $at_least_one_authorize->expires_in ,
            'access_token' => $at_least_one_authorize->access_token,
            'refresh_token' => $at_least_one_authorize->refresh_token,
            'first_time_authenticate' => false
        ];

        return \response()->json($response,200);

    }

    public function checkUser($email,$password)
    {
        $user_collection = User::where('email','=',$email)->first();
        if(!isset($user_collection)) return false;


        $user_auth = \Illuminate\Foundation\Auth\User::find($user_collection->id);

        $auth = (new BcryptHasher)->check($password,$user_auth->getAuthPassword());

        if($auth == false) return false;

        $this->authenticated_user_email = $user_collection->email;
        $this->authenticated_user_id = $user_collection->id;
        $this->authenticated_user_name = $user_collection->name;

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

        return $check_access_token;
    }
}