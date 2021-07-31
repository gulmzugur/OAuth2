<?php

namespace OAuth2\Server;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use DateInterval;
use Exception;

use OAuth2\Config\AuthorizationConfig;
use OAuth2\Config\AuthorizationGrant;
use OAuth2\Exception\ConfigException;
use OAuth2\Exception\ServerException;
use OAuth2\Extension\OIDC;
use OAuth2\OAuth2;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;



/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Server
**/

class OAuth2AuthorizationServer{

    use ResponseTrait;

    /**
     * @var AuthorizationServer $server
     */
    private $server;

    /**
     * OAuth2AuthorizationServer constructor.
     * @param AuthorizationConfig $config
     * @param AuthorizationGrant $grant
     * @param OIDC|null $oidc
     */
    function __construct(
        AuthorizationConfig $config,
        AuthorizationGrant $grant,
        OIDC $oidc = null
    )
    {
        $this->initialize($config, $oidc)->setGrantType($grant);
    }

    /**
     * @param AuthorizationGrant $grant
     * @return $this|void
     */
    private function setGrantType(AuthorizationGrant $grant): OAuth2AuthorizationServer
    {
        try {
            $this->server->enableGrantType(
                $grant->getGrantType(),
                new DateInterval($grant->getAccessTokenTTL())
            );
            return $this;
        } catch (Exception $exception) {
            $this->handleException(new ConfigException(
                'Error when applying grant type, please check your configuration.',
                $exception->getCode()
            ));
        }
    }

    /**
     * @param Exception $exception
     */
    function handleException(Exception $exception)
    {
        OAuth2::handleException($exception);
    }
    
    /**
     * @param AuthorizationConfig $config
     * @param $oidc
     * @return $this|void
     */
    private function initialize(AuthorizationConfig $config, $oidc): OAuth2AuthorizationServer
    {
        try {
            if (empty(getenv('encryption.key'))) $this->handleException(new ConfigException(
                'Cant\'t get encryption key from .env.',
                2
            ));
            $this->server = new AuthorizationServer(
                $config->getClientRepository(),
                $config->getAccessTokenRepository(),
                $config->getScopeRepository(),
                $config->getPrivateKey(),
                getenv('encryption.key'),
                ($oidc === null) ? $config->getResponseType() : $oidc->getResponseType()
            );
            return $this;
        } catch (Exception $exception) {
            $this->handleException(new ConfigException(
                'Error when initializing Authorization Server, please check your configuration.',
                $exception->getCode()
            ));
        }
    }

    /**
     * @param $request
     * @param $response
     * @return $this
     */
    function bootstrap(&$request, &$response): OAuth2AuthorizationServer
    {
        $this->request = &$request;
        $this->response = &$response;
        return $this;
    }

    /**
     * @param IncomingRequest $request
     * @return OAuth2AuthorizationServer
     */
    function withRequest(IncomingRequest $request): OAuth2AuthorizationServer
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param Response $response
     * @return OAuth2AuthorizationServer
     */
    function withResponse(Response $response): OAuth2AuthorizationServer
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return AuthorizationRequest|Response|void
     * @throws ServerException
     */
    function validateAuth()
    {
        try {
            $authRequest = $this->server->validateAuthorizationRequest(OAuth2::handleRequest($this->request));
            $authRequest->setAuthorizationApproved(true);
            return $authRequest;
        } catch (OAuthServerException $exception) {
            throw new ServerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getErrorType(),
                $exception->getHttpStatusCode(),
                $exception->getHint()
            );
        }
    }

    /**
     * @param AuthorizationRequest $authorizationRequest
     * @return ResponseInterface|Response|void
     * @throws ServerException
     */
    function completeAuth(AuthorizationRequest $authorizationRequest)
    {
        try {
            $this->return($this->server->completeAuthorizationRequest(
                $authorizationRequest,
                OAuth2::handleResponse($this->response)
            ));
        } catch (Exception $exception) {
            throw new OAuth2ServerException(
                $exception->getMessage(),
                $exception->getCode(),
                'complete_authorization_request_error',
                500
            );
        }
    }

    /**
     * @param ResponseInterface $generatedResponse
     * @return Response|void
     */
    function return(ResponseInterface $generatedResponse): Response
    {
        $this->validateRequestAndResponse();
        return OAuth2::return($generatedResponse, $this->response);
    }

    /**
     * @return void
     */
    function validateRequestAndResponse(){
        
        if (empty($this->request))
            $this->handleException(
                new ServerException(
                    'Server Request is undefined, please apply it via bootstrap().',
                    0,
                    'bootstrap_request_error',
                    500
                )
            );
        else if (empty($this->response))
            $this->handleException(
                new ServerException(
                    'Server Response is undefined, please apply it via bootstrap().',
                    1,
                    'bootstrap_response_error',
                    500
                )
            );
    }

    /**
     * @return ResponseInterface|Response|void
     * @throws ServerException
     */
    function createToken(){

        try {
            $this->return($this->server->respondToAccessTokenRequest(
                OAuth2::handleRequest($this->request),
                OAuth2::handleResponse($this->response)
            ));
        } catch (OAuthServerException $exception) {
            throw new ServerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getErrorType(),
                $exception->getHttpStatusCode(),
                $exception->getHint()
            );
        } catch (Exception $exception) {
            throw new ServerException(
                $exception->getMessage(),
                $exception->getCode(),
                'respond_access_token_error',
                500
            );
        }
    }
}
