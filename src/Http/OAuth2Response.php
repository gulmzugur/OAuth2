<?php 

namespace OAuth2\Http;

/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Http
**/

class OAuth2Response extends Response{

    /**
     * OAuth2Response constructor.
     * @param \CodeIgniter\HTTP\Response $response
     */
    function __construct(\CodeIgniter\HTTP\Response $response)
    {
        $headers = [];
        foreach ($response->getHeaders() as $key => $value) $headers[$key] = array($value->getValue());
        parent::__construct(
            $response->getStatusCode(),
            new Headers($headers)
        );
    }
}