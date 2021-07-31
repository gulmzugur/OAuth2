<?php 

namespace OAuth2\Interfaces;

/**
 * @author      Uğur Gülmez <gulmzugur@gmail.com>
 * @copyright   Copyright (c) Uğur Gülmez
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/
 * 
 * @package     OAuth2\Interfaces
**/

interface AuthorizationController{
    
    /**
     * AuthorizationController constructor.
     */
    function __construct();

    /**
     * @return mixed
     */
    function authorize();

    /**
     * @return mixed
     */
    function token();
}