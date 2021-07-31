<?php 

namespace OAuth2\Extension;

use Exception;

use OAuth2\Exception\ConfigException;
use OAuth2\Interfaces\IdentityRepositoryInterface;

use OpenIDConnectServer\ClaimExtractor;
use OpenIDConnectServer\IdTokenResponse;

/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Extension
**/

class OIDC
{
    /**
     * @var IdTokenResponse
     */
    private $responseType;

    /**
     * OIDC constructor.
     * @param IdentityRepositoryInterface $identityRepository
     * @param array $claimSet
     */
    function __construct(IdentityRepositoryInterface $identityRepository, array $claimSet)
    {
        try {
            $identityRepository = new $identityRepository();
            $this->responseType = new IdTokenResponse($identityRepository, new ClaimExtractor($claimSet));
        } catch (Exception $exception) {
            throw new ConfigException(
                'Error happened when enabling Authorization Server OIDC, please recheck your parameter.'
            );
        }
    }

    /**
     * @return IdTokenResponse
     */
    function getResponseType(): IdTokenResponse
    {
        return $this->responseType;
    }
}