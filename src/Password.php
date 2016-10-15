<?php

namespace Obullo\Auth;

use Interop\Container\ContainerInterface as Container;

/**
 * Password verify
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Password
{
    /**
     * Container
     *
     * @param Container $container container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Verify password hash
     *
     * @param string $plain plain  password
     * @param string $hash  hashed password
     *
     * @return boolean|string
     */
    public function verify($plain, $hash)
    {
        $cost = $this->container->get('Auth.PASSWORD_COST');
        $algo = $this->container->get('Auth.PASSWORD_ALGORITHM');

        if (password_verify($plain, $hash)) {
            if (password_needs_rehash($hash, $algo, array('cost' => $cost))) {
                return password_hash($plain, $algo, array('cost' => $cost));
            }
            return true;
        }
        return false;
    }
}
