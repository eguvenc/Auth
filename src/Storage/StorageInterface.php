<?php

namespace Obullo\Authentication\Storage;

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
     * @param string $block __temporary or __permanent | full key
     *
     * @return bool
     */
    public function isEmpty($block = '__permanent');

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
     * @param string $block       storage persistence type permanent / temporary
     * @param string $ttl         storage lifetime
     *
     * @return boolean
     */
    public function setCredentials(array $credentials, $pushData = null, $block = '__temporary', $ttl = null);

    /**
     * Get temporary|permanent credentials Data
     *
     * @param string $storage name
     *
     * @return void
     */
    public function getCredentials($storage = '__permanent');

    /**
     * Delete temporary|permanent credentials Data
     *
     * @param string $storage name
     *
     * @return void
     */
    public function deleteCredentials($storage = '__temporary');

    /**
     * Update identity item value
     *
     * @param string $key   string
     * @param value  $val   value
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function update($key, $val, $block = '__permanent');

    /**
     * Unset identity item
     *
     * @param string $key   string
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function remove($key, $block = '__permanent');

    /**
     * Check whether to identify exists
     *
     * @param string $block __temporary or __permanent
     *
     * @return array keys if succes otherwise false
     */
    public function getAllKeys($block = '__permanent');

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
