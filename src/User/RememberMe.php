<?php

namespace Obullo\Auth\MFA\User;

use Obullo\Auth\MFA\CookieInterface as Cookie;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Remember me token generator
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class RememberMe implements RememberMeInterface
{
    /**
     * Cookie
     *
     * @var object
     */
    protected $cookie;

    /**
     * Cookie params
     *
     * @var array
     */
    protected $params = array();

    /**
     * Constructor
     *
     * @param Cookie $cookie object
     * @param array  $params parameters
     */
    public function __construct(Cookie $cookie, array $params)
    {
        $this->cookie = $cookie;
        $this->params = $params;

        $this->cookie->setDefaults($params);
    }

    /**
     * Read recaller token
     *
     * @return string|false
     */
    public function readToken()
    {
        return $this->cookie->get($this->params['name'], false);
    }

    /**
     * Create recaller token
     *
     * @return string token
     */
    public function getToken()
    {
        $cookie = $this->getParams();
        $token  = $this->generateToken();

        $this->cookie->set($cookie['name'], $token);
        return $token;
    }

    /**
     * Remove recaller token
     *
     * @return void
     */
    public function removeToken()
    {
        $cookie = $this->getParams();
        
        $this->cookie->delete($cookie['name']);
    }

    /**
     * Returns to cookie parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
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
