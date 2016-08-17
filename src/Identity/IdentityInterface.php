<?php

namespace Obullo\Auth\MFA\Identity;

/**
 * Common Identity Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface IdentityInterface
{
    /**
     * Check user has identity
     *
     * Its ok if returns to true otherwise false
     *
     * @return boolean
     */
    public function check();

    /**
     * Opposite of check() function
     *
     * @return boolean
     */
    public function guest();

    /**
     * Set expire time
     *
     * @param int $ttl expire
     *
     * @return void
     */
    public function expire($ttl);

    /**
     * Check user is expired
     *
     * @return boolean
     */
    public function isExpired();

    /**
     * Checks new identity data available in storage.
     *
     * @return boolean
     */
    public function exists();

    /**
     * Returns to unix microtime value.
     *
     * @return string
     */
    public function getTime();

    /**
     * Get all identity attributes
     *
     * @return array
     */
    public function getArray();

    /**
     * Returns to "1" user if used remember me
     *
     * @return integer
     */
    public function getRememberMe();

    /**
     * Returns to remember token
     *
     * @return integer
     */
    public function getRememberToken();

    /**
     * Sets authority of user to "0" don't touch to cached data
     *
     * @return void
     */
    public function logout();

    /**
     * Destroy permanent identity
     *
     * @return void
     */
    public function destroy();

    /**
     * Update remember token if it exists in the memory and browser header
     *
     * @return int|boolean
     */
    public function updateRememberToken();

    /**
     * Refresh the rememberMe token value
     *
     * @return int|boolean
     */
    public function refreshRememberToken();

    /**
     * Removes recaller cookie from user browser
     *
     * @return void
     */
    public function forgetMe();
}
