<?php 
namespace OAuth2\Interfaces\Http;

/**
 * Part of Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 *
 * Interface EnvironmentInterface
 * @package OAuth2\Interfaces\Http
 */
interface EnvironmentInterface
{
    /**
     * Create mock environment
     *
     * @param array $settings Array of custom environment keys and values
     *
     * @return static
     */
    public static function mock(array $settings = []): EnvironmentInterface;
}
