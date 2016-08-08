<?php

namespace Obullo\Authentication\Storage;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Memcached storage
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Memcached extends AbstractStorage
{
    /**
     * Http request
     *
     * @var object
     */
    protected $request;

    /**
     * Memcached
     *
     * @var object
     */
    protected $memcached;

    /**
     * Constructor
     *
     * @param \Memcached $memcached memcached
     * @param array      $options   options
     *
     * @return void
     */
    public function __construct(\Memcached $memcached, Request $request, $options = array())
    {
        $this->memcached = $memcached;
        $this->request   = $request;
        
        parent::__construct($options);
    }

    /**
     * Returns true if temporary credentials does "not" exists
     *
     * @param string $block __temporary or __permanent | full key
     *
     * @return bool
     */
    public function isEmpty($block = '__permanent')
    {
        $exists = $this->getCredentials($block);
        return ($exists) ? false : true;
    }

    /**
     * Match the user credentials.
     *
     * @return object|false
     */
    public function query()
    {
        if (! $this->isEmpty('__permanent')) {  // If user has cached auth return to data otherwise false

            $data = $this->getCredentials();

            if ($data == false || count($data) == 0 || ! isset($data['__isAuthenticated'])) {
                return false;
            }
            return $data;
        }
        return false;
    }
    
    /**
     * Update credentials
     *
     * @param array  $credentials user identity old data
     * @param mixed  $pushData    push to identity data
     * @param string $block       storage persistence type permanent / temporary
     * @param string $ttl         storage lifetime
     *
     * @return boolean
     */
    public function setCredentials(array $credentials, $pushData = null, $block = '__temporary', $ttl = null)
    {
        if ($this->getIdentifier() == null) {
            return false;
        }
        $this->data[$block] = array($this->getLoginId() => $credentials);
        if (! empty($pushData) && is_array($pushData)) {
            $this->data[$block] = array($this->getLoginId() => array_merge($credentials, $pushData));
        }
        $allData  = $this->memcached->get($this->getMemoryBlockKey($block));  // Get all data
        $lifetime = ($ttl == null) ? $this->getMemoryBlockLifetime($block) : (int)$ttl;

        if ($allData == false) {
            $allData = array();
        }
        $this->memcached->set(
            $this->getMemoryBlockKey($block),
            array_merge($allData, $this->data[$block]),
            $lifetime
        );
        return true;
    }

    /**
     * Get Temporary Credentials Data
     *
     * @param string $block name
     *
     * @return void
     */
    public function getCredentials($block = '__permanent')
    {
        if ($this->getIdentifier() == null) {
            return false;
        }
        $data = $this->memcached->get($this->getBlock($block));
        if (isset($data[$this->getLoginId()])) {
            return $data[$this->getLoginId()];
        }
        return false;
    }

    /**
     * Deletes memory block
     *
     * @param string $block name or key
     *
     * @return void
     */
    public function deleteCredentials($block = '__permanent')
    {
        $loginID = $this->getLoginId();
        $credentials = $this->memcached->get($this->getBlock($block));  // Don't do container cache

        if (! isset($credentials[$loginID])) {  // already removed
            return;
        }
        unset($credentials[$loginID]);
        $this->memcached->set(
            $this->getMemoryBlockKey($block),
            $credentials,
            $this->getMemoryBlockLifetime($block)
        );
        $credentials = $this->memcached->get($this->getBlock($block)); // Destroy auth block if its empty
        if (empty($credentials)) {
            $this->memcached->delete($this->getBlock($block));
        }
    }

    /**
     * Update identity item value
     *
     * @param string $key   string
     * @param value  $val   value
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function update($key, $val, $block = '__permanent')
    {
        $data = $this->getCredentials($block);
        $data[$key] = $val;
        $this->setCredentials($data, null, $block);
    }

    /**
     * Unset identity item
     *
     * @param string $key   string
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function remove($key, $block = '__permanent')
    {
        $data = $this->getCredentials($block);
        unset($data[$key]);
        $this->setCredentials($data, null, $block);
    }

    /**
     * Get all keys
     *
     * @param string $block __temporary or __permanent
     *
     * @return array keys if succes otherwise false
     */
    public function getAllKeys($block = '__permanent')
    {
        return $this->memcached->get($this->getBlock($block));
    }

    /**
     * Returns to full identity block name
     *
     * @param string $block name
     *
     * @return string
     */
    public function getMemoryBlockKey($block = '__temporary')
    {
        /**
         * In here memcached like storages use $this->storage->getUserId()
         * but redis like storages use $this->storage->getIdentifier();
         */
        return $this->getCacheKey(). ':' .$block. ':' .$this->getUserId();  // Create unique key
    }
    
    /**
     * Returns to storage prefix key of identity data
     *
     * @param string $block memory block
     *
     * @return string
     */
    public function getUserKey($block = '__temporary')
    {
        return $this->getCacheKey(). ':' .$block. ':'.$this->getUserId();
    }

    /**
     * Returns to database sessions
     *
     * @return array
     */
    public function getUserSessions()
    {
        $sessions = array();
        $dbSessions = $this->memcached->get($this->getMemoryBlockKey('__permanent'));

        if ($dbSessions == false) {
            return $sessions;
        }
        foreach ($dbSessions as $loginID => $val) {
            if (isset($val['__isAuthenticated'])) {
                $sessions[$loginID]['__isAuthenticated'] = $val['__isAuthenticated'];
                $sessions[$loginID]['__time'] = $val['__time'];
                $sessions[$loginID]['__id']  = $this->getUserId();
                $sessions[$loginID]['__key'] = $this->getMemoryBlockKey('__permanent');
                $sessions[$loginID]['__agent'] = $val['__agent'];
                $sessions[$loginID]['__ip']  = $val['__ip'];
                $sessions[$loginID]['__lastActivity']  = $val['__lastActivity'];
            }
        }
        return $sessions;
    }

    /**
     * Kill session using by login id
     *
     * @param integer $loginID login id e.g. 87060e89 ( user@example.com:87060e89 )
     *
     * @return void
     */
    public function killSession($loginID)
    {
        $data = $this->memcached->get($this->getMemoryBlockKey('__permanent'));

        unset($data[$loginID]);
        $this->memcached->set(
            $this->getMemoryBlockKey('__permanent'),
            $data,
            $this->getMemoryBlockLifetime('__permanent')
        );
    }
}
