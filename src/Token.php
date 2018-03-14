<?php
namespace Batmahir\OAuth2;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Auth;

class Token extends TokenGuard
{
    public function __construct(ResourceServer $server, UserProvider $provider, TokenRepository $tokens, ClientRepository $clients, Encrypter $encrypter)
    {
        parent::__construct($server, $provider, $tokens, $clients, $encrypter);
    }

    public function createTokenGuarObject()
    {
        $authGuardApiConfig = config('auth.guards.api');
        $tokenGuardObj
            = new \Batmahir\OAuth2\Token(
            resolve(ResourceServer::class),
            Auth::createUserProvider($authGuardApiConfig['provider']),
            resolve(TokenRepository::class),
            resolve(ClientRepository::class),
            resolve('encrypter')
        );

        return $tokenGuardObj;
    }

    public function validateToken(Request $request)
    {
        $token = $this->getTokenCredentials($request);

        return $token;
    }

    protected function getTokenCredentials($request)
    {
        // First, we will convert the Symfony request to a PSR-7 implementation which will
        // be compatible with the base OAuth2 library. The Symfony bridge can perform a
        // conversion for us to a Zend Diactoros implementation of the PSR-7 request.
        $psr = (new DiactorosFactory)->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);

            return \json_decode(\json_encode($psr->getAttributes()));
        } catch (OAuthServerException $e) {
            return Container::getInstance()->make(
                ExceptionHandler::class
            )->report($e);
        }
    }


}