<?php

namespace Obullo\Auth\Storage;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Memcached storage
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Memcached extends AbstractStorage
{
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
    public function __construct(\Memcached $memcached, $options = array())
    {
        $this->memcached = $memcached;
        
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
        $data = $credentials;
        if (! empty($pushData) && is_array($pushData)) {
            $data = array_merge($credentials, $pushData);
        }
        $lifetime = (int)$ttl;
        if ($ttl == null) {
            $lifetime = ($credentials['__isTemporary'] == 1) ?  $this->getTemporaryBlockLifetime() : $this->getPermanentBlockLifetime();
        }
        $key = $this->getMemoryBlockKey();

        $this->memcached->set($key, $data, $lifetime);
        $this->setSessionIndex($data, $lifetime);
        return true;
    }

    /**
     * Get user credentials data
     *
     * @return void
     */
    public function getCredentials()
    {
        if ($this->getIdentifier() == null) {
            return false;
        }
        return $this->memcached->get($this->getMemoryBlockKey());
    }
   
    /**
     * Deletes memory block completely
     *
     * @param string $block name or key
     *
     * @return void
     */
    public function deleteCredentials()
    {
        return $this->memcached->delete($this->getMemoryBlockKey());
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
    public function getActiveSessions()
    {
        $sessions = array();
        if ($sessionIndex = $this->getSessionIndex()) {
            foreach ($sessionIndex as $loginID => $val) {
                $key = $this->getStoreKey().':'.$this->getUserId().':'.$loginID;

                $maxLifetime = $this->getPermanentBlockLifetime() + $this->getTemporaryBlockLifetime();
                if ($val['lastActivity'] > $maxLifetime) {
                    $sessions[$loginID] = $this->memcached->get($key);
                }
            }
        }
        return $sessions;
    }
    
    /**
     * Returns to database sessions
     *
     * @return array
     */
    public function getUserSessions()
    {
        $sessions = array();
        $dbSessions = $this->getActiveSessions();

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
        $this->memcached->delete($this->getStoreKey().':'.$this->getUserId().':'.$loginID);
    }

    /**
     * Keeps login id index of all users
     *
     * We use this method to get active sessions.
     *
     * @param array   $data data
     * @param integer $lifetime lifetime
     *
     * @return boolean
     */
    public function setSessionIndex($data, $lifetime)
    {
        $key = $this->getUserKey().':session_index';
        $id  = $this->getUserId();

        $index = $this->memcached->get($key);
        if ($index == false) {
            $index = array();
        }
        $index[$id][$this->getLoginId()] = [
            'lastActivity' => $data['__lastActivity'],
        ];
        return $this->memcached->set($key, $index, $lifetime);
    }

    /**
     * Returns to index of logged users sessions
     *
     * @return array
     */
    public function getSessionIndex()
    {
        $index = $this->memcached->get($this->getUserKey().':session_index');

        return isset($index[$this->getUserId()]) ? $index[$this->getUserId()] : array();
    }

    /**
     * Removes session index
     *
     * @return boolean
     */
    public function deleteSessionIndex()
    {
        return $this->memcached->delete($this->getUserKey().':session_index');
    }
}
