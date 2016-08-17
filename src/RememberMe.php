<?php

namespace Obullo\Auth\MFA;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Remember me token generator
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class RememberMe
{
    /**
     * Request
     *
     * @var object
     */
    protected $request;

    /**
     * Cookie params
     *
     * @var array
     */
    protected $cookieParams = array();

    /**
     * Constructor
     *
     * @param array $cookieParams parameters
     */
    public function __construct(Request $request, array $cookieParams)
    {
        $this->request = $request;
        $this->cookieParams = $cookieParams;
    }

    /**
     * Read recaller token
     *
     * @return string
     */
    public function readToken()
    {
        $name    = $this->cookieParams['name'];
        $cookies = $this->request->getCookieParams();

        return isset($cookies[$name]) ? $cookies[$name] : false;
    }

    /**
     * Create recaller token
     *
     * @return string token
     */
    public function getToken()
    {
        $cookie = $this->getCookieParams();
        $token  = $this->generateToken();

        setcookie(
            $cookie['name'],
            $token,
            $cookie['expire'] + time(),
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
    public function removeToken()
    {
        $cookie = $this->getCookieParams();

        setcookie(
            $cookie['name'],
            null,
            -1,
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
    }

    /**
     * Returns to cookie parameters
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Generate recaller token
     *
     * @return string
     */
    public function generateToken()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < 32; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
}
