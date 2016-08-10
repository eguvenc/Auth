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
     * @return bool
     */
    public function isEmpty()
    {
        $exists = $this->getCredentials();
        return ($exists) ? false : true;
    }

    /**
     * Match the user credentials.
     *
     * @return object|false
     */
    public function query()
    {
        if (! $this->isEmpty()) {  // If user has cached auth return to data otherwise false

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
     * @param string $ttl         storage lifetime
     *
     * @return boolean
     */
    public function setCredentials(array $credentials, $pushData = null, $ttl = null)
    {
        if ($this->getIdentifier() == null) {
            return false;
        }
        $this->data = array($this->getLoginId() => $credentials);
        if (! empty($pushData) && is_array($pushData)) {
            $this->data = array($this->getLoginId() => array_merge($credentials, $pushData));
        }
        $allData = $this->memcached->get($this->getMemoryBlockKey());  // Get all data

        $lifetime = (int)$ttl;
        if ($ttl == null) {
            $lifetime = ($credentials['__isTemporary'] == 1) ?  $this->getTemporaryBlockLifetime() : $this->getPermanentBlockLifetime();
        }
        if ($allData == false) {
            $allData = array();
        }
        $this->memcached->set(
            $this->getMemoryBlockKey(),
            array_merge($allData, $this->data),
            $lifetime
        );
        return true;
    }

    /**
     * Get credentials data
     *
     * @return void
     */
    public function getCredentials()
    {
        if ($this->getIdentifier() == null) {
            return false;
        }
        $data = $this->memcached->get($this->getMemoryBlockKey());
        if (isset($data[$this->getLoginId()])) {
            return $data[$this->getLoginId()];
        }
        return false;
    }

    /**
     * Deletes memory block
     *
     * @return void
     */
    public function deleteCredentials()
    {
        $loginID = $this->getLoginId();
        $credentials = $this->memcached->get($this->getMemoryBlockKey());  // Don't do container cache

        if (! isset($credentials[$loginID])) {  // already removed
            return;
        }
        unset($credentials[$loginID]);
        $this->memcached->set(
            $this->getMemoryBlockKey(),
            $credentials,
            $this->getPermanentBlockLifetime()
        );
        $credentials = $this->memcached->get($this->getMemoryBlockKey()); // Destroy auth block if empty
        if (empty($credentials)) {
            $this->memcached->delete($this->getMemoryBlockKey());
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
    public function update($key, $val)
    {
        $data = $this->getCredentials();
        $data[$key] = $val;
        $this->setCredentials($data, null);
    }

    /**
     * Update temporay data
     *
     * @param string $key key
     * @param mixed  $val val
     *
     * @return void
     */
    public function updateTemporary($key, $val)
    {
        $this->update($key, $val);
    }

    /**
     * Unset identity item
     *
     * @param string $key string
     *
     * @return boolean|integer
     */
    public function remove($key)
    {
        $data = $this->getCredentials();
        unset($data[$key]);
        $this->setCredentials($data, null);
    }

    /**
     * Get all keys
     *
     * @return array keys if succes otherwise false
     */
    public function getAllKeys()
    {
        return $this->memcached->get($this->getMemoryBlockKey());
    }

    /**
     * Returns to full identity block name
     *
     * @return string
     */
    public function getMemoryBlockKey()
    {
        /**
         * In here memcached like storages use $this->storage->getUserId()
         * but redis like storages use $this->storage->getIdentifier();
         */
        return $this->getCacheKey(). ':' .$this->getUserId();  // Create unique key
    }
    
    /**
     * Returns to storage prefix key of identity data
     *
     * @return string
     */
    public function getUserKey()
    {
        return $this->getCacheKey(). ':' .$this->getUserId();
    }

    /**
     * Returns to database sessions
     *
     * @return array
     */
    public function getUserSessions()
    {
        $sessions = array();
        $dbSessions = $this->memcached->get($this->getMemoryBlockKey());

        if ($dbSessions == false) {
            return $sessions;
        }
        foreach ($dbSessions as $loginID => $val) {
            if (isset($val['__isAuthenticated'])) {
                $sessions[$loginID]['__isAuthenticated'] = $val['__isAuthenticated'];
                $sessions[$loginID]['__time'] = $val['__time'];
                $sessions[$loginID]['__id']  = $this->getUserId();
                $sessions[$loginID]['__key'] = $this->getMemoryBlockKey();
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
        $data = $this->memcached->get($this->getMemoryBlockKey());

        unset($data[$loginID]);
        $this->memcached->set(
            $this->getMemoryBlockKey(),
            $data,
            $this->getPermanentBlockLifetime()
        );
    }
}
