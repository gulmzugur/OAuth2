<?php 
namespace OAuth2\Http;

/**
 * Part of Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 *
 * Provides a PSR-7 implementation of a reusable raw request body
 *
 * @package OAuth2\Http
 */
class RequestBody extends Body
{
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);
        parent::__construct($stream);
    }
}
