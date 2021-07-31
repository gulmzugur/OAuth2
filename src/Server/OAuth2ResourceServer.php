<?php 

namespace OAuth2\Server;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use Exception;

use OAuth2\Config\ResourceConfig;
use OAuth2\Exception\ConfigException;
use OAuth2\Exception\ServerException;
use OAuth2\OAuth2;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;

/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Server
**/

class OAuth2ResourceServer{

    /**
     * @var ResourceServer
     */
    private $server;

    /**
     * OAuth2ResourceServer constructor.
     * @param ResourceConfig $config
     */
    function __construct(ResourceConfig $config)
    {
        $this->initialize($config);
    }

    /**
     * @param ResourceConfig $config
     * @return $this|void
     */
    private function initialize(ResourceConfig $config): OAuth2ResourceServer
    {
        try {
            $this->server = new ResourceServer(
                $config->getAccessTokenRepository(),
                $config->getPublicKey(),
                $config->getAuthorizationValidator()
            );
            return $this;
        } catch (Exception $exception) {
            $this->handleException(new ConfigException(
                'Error when initializing Resource Server, please check your configuration.',
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
     * @param RequestInterface $request
     * @throws ServerException
     */
    function validate(RequestInterface &$request)
    {
        try {
            $response = $this->server->validateAuthenticatedRequest(
                OAuth2::handleRequest(
                    new IncomingRequest(config('app'),
                        $request->uri, $request->getBody(),
                        $request->getUserAgent()
                    )
                )
            );
            $request->setHeader('authorization', $response->getAttributes());
        } catch (OAuthServerException $exception) {
            throw new OAuth2ServerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getErrorType(),
                $exception->getHttpStatusCode(),
                $exception->getHint()
            );
        }
    }
}