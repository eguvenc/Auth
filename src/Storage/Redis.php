<?php

namespace Obullo\Auth\Storage;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Redis storage
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Redis extends AbstractStorage
{
    /**
     * Redis
     *
     * @var object
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param \Redis $redis   redis
     * @param array  $options options
     *
     * @return void
     */
    public function __construct(\Redis $redis, $options = array())
    {
        $this->redis = $redis;
        
        parent::__construct($options);
    }

    /**
     * Returns true if credentials does "not" exists
     *
     * @return bool
     */
    public function isEmpty()
    {
        $exists = $this->redis->exists($this->getMemoryBlockKey());
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
            $lifetime = ($credentials['__isTemporary'] == 1) ? $this->getTemporaryBlockLifetime() : $this->getPermanentBlockLifetime();
        }
        $key = $this->getMemoryBlockKey();

        $this->redis->hMSet($key, $data);
        if ($lifetime > 0) {
            $this->redis->setTimeout($key, $lifetime);
        }
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
        return $this->redis->hGetAll($this->getMemoryBlockKey());
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
        return $this->redis->delete($this->getMemoryBlockKey());
    }

    /**
     * Update permanent data
     *
     * @param string $key  string
     * @param value  $val  value
     *
     * @return boolean|integer
     */
    public function update($key, $val)
    {
        $lifetime = $this->getPermanentBlockLifetime(); // Refresh permanent expiration time

        $this->redis->hSet($this->getMemoryBlockKey(), $key, $val);

        if ($lifetime > 0) {
            $this->redis->setTimeout($key, $lifetime);
        }
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
        $this->redis->hSet($this->getMemoryBlockKey(), $key, $val);
    }

    /**
     * Remove data
     *
     * @param string $key string
     *
     * @return boolean|integer
     */
    public function remove($key)
    {
        return $this->redis->hDel($this->getMemoryBlockKey(), $key);
    }

    /**
     * Get session data of current user
     *
     * @return array keys if succes otherwise false
     */
    public function getActiveSessions()
    {
        $data = $this->redis->keys($this->getUserKey().':*');

        if (isset($data[0])) {
            return $data;
        }
        return false;
    }

    /**
     * Return to all sessions of current user
     *
     * @return array
     */
    public function getUserSessions()
    {
        $sessions   = array();
        $identifier = $this->getUserId();
        $key        = $this->getStoreKey().':';
        $dbSessions = $this->getActiveSessions();  // $this->redis->keys($key.$identifier.':*');

        if ($dbSessions == false) {
            return $sessions;
        }
        foreach ($dbSessions as $val) {
            $exp = explode(':', $val);
            $loginID = end($exp);
            $value = $this->redis->hGet($key.$identifier.':'.$loginID, '__isAuthenticated');
            if ($value !== false) {
                $sessions[$loginID]['__isAuthenticated'] = $value;
                $sessions[$loginID]['__time'] = $this->redis->hGet($key.$identifier.':'.$loginID, '__time');
                $sessions[$loginID]['__id']  = $identifier;
                $sessions[$loginID]['__key'] = $key.$identifier.':'.$loginID;
                $sessions[$loginID]['__agent'] = $this->redis->hGet($key.$identifier.':'.$loginID, '__agent');
                $sessions[$loginID]['__ip']  = $this->redis->hGet($key.$identifier.':'.$loginID, '__ip');
                $sessions[$loginID]['__lastActivity']  = $this->redis->hGet($key.$identifier.':'.$loginID, '__lastActivity');
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
        $this->redis->delete($this->getStoreKey().':'.$this->getUserId().':'.$loginID);
    }
}
