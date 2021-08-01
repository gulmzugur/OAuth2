<?php 
namespace OAuth2\Exception;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Part of Slim Framework (https://slimframework.com)
 * 
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Exception
**/


class InvalidMethodException extends InvalidArgumentException{

    /**
     * @var ServerRequestInterface
    **/
    protected $request;

    /**
     * @param ServerRequestInterface $request
     * @param string $method
    **/
    public function __construct(ServerRequestInterface $request, string $method){

        $this->request = $request;
        parent::__construct(sprintf('Unsupported HTTP method "%s" provided', $method));
    }

    /**
     * @return ServerRequestInterface
    **/
    public function getRequest(): ServerRequestInterface{

        return $this->request;
    }
}
