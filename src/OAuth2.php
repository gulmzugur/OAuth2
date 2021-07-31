<?php 
namespace OAuth2;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use DateInterval;
use Exception;

use OAuth2\Config\AuthorizationConfig;
use OAuth2\Config\AuthorizationGrant;
use OAuth2\Config\ResourceConfig;
use OAuth2\Exception\ConfigException;
use OAuth2\Exception\ServerException;
use OAuth2\Extension\OIDC;
use OAuth2\Http\OAuth2Request;
use OAuth2\Http\OAuth2Response;
use OAuth2\interfaces\IdentityRepositoryInterface;
use OAuth2\Server\OAuth2AuthorizationServer;
use OAuth2\Server\OAuth2ResourceServer;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

use Psr\Http\Message\ResponseInterface;


/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Config
**/

abstract class OAuth2{
    
    /**
     * @param AuthorizationConfig $config
     * @param AuthorizationGrant $grant
     * @param OIDC|null $oidc
     * @return OAuth2AuthorizationServer
    **/
    static function initializeAuthServer(
        AuthorizationConfig $config,
        AuthorizationGrant $grant,
        OIDC $oidc = null
    ): OAuth2AuthorizationServer {
        return new OAuth2AuthorizationServer($config, $grant, $oidc);
    }

    /**
     * @param ResourceConfig $config
     * @return OAuth2ResourceServer
    **/
    static function initializeResourceServer(ResourceConfig $config): OAuth2ResourceServer {
        return new OAuth2ResourceServer($config);
    }

    /**
     * @param IncomingRequest $request
     * @return OAuth2Request
    **/
    static function handleRequest(IncomingRequest $request): OAuth2Request {
        return (new OAuth2Request($request))->withParsedBody($request->getPost());
    }

    /**
     * @param Response $response
     * @return OAuth2Response
    **/
    static function handleResponse(Response $response): OAuth2Response {
        return new OAuth2Response($response);
    }

    /**
     * @param  ResponseInterface $generatedResponse
     * @param  Response $response
     * @return Response
    **/
    static function return(ResponseInterface $generatedResponse, Response $response): Response {
        $formattedResponse = $response
            ->setContentType('application/json')
            ->setStatusCode($generatedResponse->getStatusCode(), $generatedResponse->getReasonPhrase())
            ->setHeader('Location', $generatedResponse->getHeader('Location'))
            ->setBody($generatedResponse->getBody());
        echo $formattedResponse->getBody();
        return $formattedResponse;
    }

    /**
     * @param Exception $exception
    **/
    static function handleException(Exception $exception){
        header('Content-Type: application/json');
        if ($exception instanceof ServerException) {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getHttpStatusCode() . ' ' . $exception->getMessage());
            $error = [
                'code' => $exception->getCode(),
                'messages' => $exception->getMessage()
            ];
            if (!empty($exception->getHint())) $error['hint'] = $exception->getHint();
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            $error = [
                'code' => $exception->getCode(),
                'messages' => $exception->getMessage()
            ];
        }
        echo json_encode($error);
        exit;
    }

    /**
     * @param $something
     * @param bool $prettify
     * @param bool $asJSON
     * @return void
    **/
    static function debug($something, $prettify = true, $asJSON = false){
        echo ($prettify === true) ? '<pre>' : '';
        ($asJSON === true) ? print_r(json_encode($something)) : print_r($something);
        echo ($prettify === true) ? '</pre>' : '';
        exit;
    }

    /**
     * @param ClientRepositoryInterface $clientRepository
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param ScopeRepositoryInterface $scopeRepository
     * @param $privateKey
     * @param ResponseTypeInterface|null $responseType
     * @return AuthorizationConfig
    **/
    static function withAuthorizationConfig(
        ClientRepositoryInterface $clientRepository,
        AccessTokenRepositoryInterface $accessTokenRepository,
        ScopeRepositoryInterface $scopeRepository,
        $privateKey,
        ResponseTypeInterface $responseType = null
    ): AuthorizationConfig {
        if (is_string($privateKey)) $privateKey = ['path' => $privateKey];
        return new AuthorizationConfig(
            $clientRepository, $accessTokenRepository, $scopeRepository, $privateKey, $responseType
        );
    }

    /**
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     * @param $publicKey
     * @param AuthorizationValidatorInterface|null $authorizationValidator
     * @return ResourceConfig
    **/
    static function withResourceConfig(
        AccessTokenRepositoryInterface $accessTokenRepository,
        $publicKey,
        AuthorizationValidatorInterface $authorizationValidator = null
    ): ResourceConfig {
        if (is_string($publicKey)) $publicKey = ['path' => $publicKey];
        return new ResourceConfig($accessTokenRepository, $publicKey, $authorizationValidator);
    }

    /**
     * @param IdentityRepositoryInterface $identityRepository
     * @param array $claimSet
     * @return OIDC
    **/
    static function withOIDC(
        IdentityRepositoryInterface $identityRepository, array $claimSet = []
    ): OIDC {
        return new OIDC($identityRepository, $claimSet);
    }

    /**
     * @param string $accessTokenTTL
     * @return AuthorizationGrant
    **/
    static function withClientCredentialsGrant(
        string $accessTokenTTL = 'PT1H'
    ): AuthorizationGrant {
        try {
            return new AuthorizationGrant(
                AuthorizationGrant::ClientCredentials,
                new ClientCredentialsGrant(),
                $accessTokenTTL
            );
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened initializing grant type, please recheck your parameter.',
                $exception->getCode()
            );
        }
    }

    /**
     * @param UserRepositoryInterface $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @param string $refreshTokenTTL
     * @param string $accessTokenTTL
     * @return AuthorizationGrant
    **/
    static function withPasswordGrant(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        string $refreshTokenTTL = 'P1M',
        string $accessTokenTTL = 'PT1H'
    ): AuthorizationGrant {
        try {
            $passwordGrant = new PasswordGrant($userRepository, $refreshTokenRepository);
            $passwordGrant->setRefreshTokenTTL(new DateInterval($refreshTokenTTL));
            return new AuthorizationGrant(
                AuthorizationGrant::PasswordCredentials,
                $passwordGrant,
                $accessTokenTTL
            );
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened initializing grant type, please recheck your parameter.',
                $exception->getCode()
            );
        }
    }

    /**
     * @param AuthCodeRepositoryInterface $authCodeRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @param string $authCodeTTL
     * @param string $refreshTokenTTL
     * @param string $accessTokenTTL
     * @return AuthorizationGrant
    **/
    static function withAuthorizationCodeGrant(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        string $authCodeTTL = 'PT10M',
        string $refreshTokenTTL = 'P1M',
        string $accessTokenTTL = 'PT1H'
    ): AuthorizationGrant {
        try {
            $authCodeGrant = new AuthCodeGrant($authCodeRepository, $refreshTokenRepository, new DateInterval($authCodeTTL));
            $authCodeGrant->setRefreshTokenTTL(new DateInterval($refreshTokenTTL));
            return new AuthorizationGrant(
                AuthorizationGrant::AuthorizationCode,
                $authCodeGrant,
                $accessTokenTTL
            );
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened initializing grant type, please recheck your parameter.',
                $exception->getCode()
            );
        }
    }

    /**
     * @param string $accessTokenTTL
     * @return AuthorizationGrant
    **/
    static function withImplicitGrant(
        string $accessTokenTTL = 'PT1H'
    ): AuthorizationGrant {
        try {
            return new AuthorizationGrant(
                AuthorizationGrant::Implicit,
                new ImplicitGrant(new DateInterval($accessTokenTTL)),
                $accessTokenTTL
            );
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened initializing grant type, please recheck your parameter.',
                $exception->getCode()
            );
        }
    }

    /**
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @param string $refreshTokenTTL
     * @param string $accessTokenTTL
     * @return AuthorizationGrant
     */
    static function withRefreshTokenGrant(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        string $refreshTokenTTL = 'P1M',
        string $accessTokenTTL = 'PT1H'
    ): AuthorizationGrant {
        try {
            $refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepository);
            $refreshTokenGrant->setRefreshTokenTTL(new DateInterval($refreshTokenTTL));
            return new AuthorizationGrant(
                AuthorizationGrant::RefreshToken,
                $refreshTokenGrant,
                $accessTokenTTL
            );
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened initializing grant type, please recheck your parameter.',
                $exception->getCode()
            );
        }
    }
}