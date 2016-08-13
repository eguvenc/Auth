<?php

namespace Obullo\Auth\MFA\Storage;

/**
 * Storage Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface StorageInterface
{
    /**
     * Returns true if temporary credentials "not" exists
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Get credentials and check authority
     *
     * @return mixed bool
     */
    public function query();

    /**
     * Update credentials
     *
     * @param array  $credentials identity data
     * @param mixed  $pushData    push to identity data
     * @param string $ttl         storage lifetime
     *
     * @return boolean
     */
    public function setCredentials(array $credentials, $pushData = null, $ttl = null);

    /**
     * Get temporary|permanent credentials Data
     *
     * @param string $storage name
     *
     * @return void
     */
    public function getCredentials();

    /**
     * Delete temporary|permanent credentials Data
     *
     * @param string $storage name
     *
     * @return void
     */
    public function deleteCredentials();

    /**
     * Update identity item value
     *
     * @param string $key  string
     * @param value  $val  value
     *
     * @return boolean|integer
     */
    public function update($key, $val);

    /**
     * Unset identity item
     *
     * @param string $key string
     *
     * @return boolean|integer
     */
    public function remove($key);

    /**
     * Returns to login keys of user
     *
     * @return array keys if succes otherwise false
     */
    public function getActiveSessions();

    /**
     * Returns to database sessions
     *
     * @return array
     */
    public function getUserSessions();

    /**
     * Kill session using by login id
     *
     * @param integer $loginId login id max e.g. 87060e89 ( user[at]example.com:87060e89 )
     *
     * @return void
     */
    public function killSession($loginId);
}
