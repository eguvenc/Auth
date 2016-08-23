<?php

namespace Obullo\Auth\MFA;

/**
 * Cookie Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface CookieInterface
{
    /**
     * Get request cookie
     *
     * @param  string $name    Cookie name
     * @param  mixed  $default Cookie default value
     *
     * @return mixed Cookie value if present, else default
     */
    public function get($name, $default = null);

    /**
     * Set response cookie
     *
     * @param string       $name  Cookie name
     * @param string|array $value Cookie value, or cookie properties
     */
    public function set($name, $value);

    /**
    * Delete a cookie
    *
    * @param string|array $name cookie
    *
    * @return boolean
    */
    public function delete($name = null);

    /**
     * Convert to `Set-Cookie` headers
     *
     * @return string[]
     */
    public function toHeaders();
}
