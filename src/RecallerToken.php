<?php

namespace Obullo\Auth;

use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Remember me token generator
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class RecallerToken
{
    /**
     * Request
     *
     * @var object
     */
    protected $request;

    /**
     * Container
     *
     * @var object
     */
    protected $container;

    /**
     * Constructor
     *
     * @param array  $params parameters
     */
    public function __construct(Request $request, Container $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

    /**
     * Create recaller token
     *
     * @return string token
     */
    public function create()
    {
        $cookie = $this->container->get('Auth.RECALLER_COOKIE');
        $token  = $this->generate();

        if (defined('STDIN')) {
            return $token;
        }
        setcookie(
            $cookie['name'],
            $token,
            time() + $cookie['expire'],
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httpOnly']
        );
        return $token;
    }

    /**
     * Remove recaller token
     *
     * @return void
     */
    public function remove()
    {
        $cookie = $this->container->get('Auth.RECALLER_COOKIE');
        
        if (defined('STDIN')) {
            return;
        }
        setcookie(
            $cookie['name'],
            null,
            -1,
            $cookie['path']
        );
    }

    /**
     * Generate recaller token
     *
     * @return string
     */
    public function generate()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < 32; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
}
