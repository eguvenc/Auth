<?php

namespace Obullo\Authentication\Storage;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Redis storage
 *
 * @copyright 2009-2016 Obullo
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
     * Http request
     *
     * @var object
     */
    protected $request;

    /**
     * Constructor
     *
     * @param \Redis $redis   redis
     * @param array  $options options
     *
     * @return void
     */
    public function __construct(\Redis $redis, Request $request, $options = array())
    {
        $this->redis    = $redis;
        $this->request  = $request;
        
        parent::__construct($options);
    }

    /**
     * Returns true if credentials does "not" exists
     *
     * @param string $block __temporary or __permanent | full key
     *
     * @return bool
     */
    public function isEmpty($block = '__permanent')
    {
        $exists = $this->redis->exists($this->getBlock($block));
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

            $data = $this->getCredentials('__permanent');

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
        $data = $credentials;
        if (! empty($pushData) && is_array($pushData)) {
            $data = array_merge($credentials, $pushData);
        }
        $lifetime = ($ttl == null) ? $this->getMemoryBlockLifetime($block) : (int)$ttl;

        $key = $this->getMemoryBlockKey($block);

        $this->redis->hMSet($key, $data);
        if ($lifetime > 0) {
            $this->redis->setTimeout($key, $lifetime);
        }
    }

    /**
     * Get user credentials data
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
        return $this->redis->hGetAll($this->getBlock($block));
    }

    /**
     * Deletes memory block completely
     *
     * @param string $block name or key
     *
     * @return void
     */
    public function deleteCredentials($block = '__permanent')
    {
        return $this->redis->delete($this->getBlock($block));
    }

    /**
     * Update data
     *
     * @param string $key   string
     * @param value  $val   value
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function update($key, $val, $block = '__permanent')
    {
        $lifetime = ($block == '__permanent') ? $this->getMemoryBlockLifetime($block) : 0;  // Refresh permanent expiration time

        $this->redis->hSet($this->getMemoryBlockKey($block), $key, $val);

        if ($lifetime > 0) {
            $this->redis->setTimeout($key, $lifetime);
        }
    }

    /**
     * Remove data
     *
     * @param string $key   string
     * @param string $block block key
     *
     * @return boolean|integer
     */
    public function remove($key, $block = '__permanent')
    {
        return $this->redis->hDel($this->getMemoryBlockKey($block), $key);
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
        $data = $this->redis->keys($this->getUserKey($block).':*');

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
        $key        = $this->cacheKey.':__permanent:';
        $dbSessions = $this->redis->keys($key.$identifier.':*');

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
        $this->deleteCredentials($this->cacheKey.':__permanent:'.$this->getUserId().':'.$loginID);
    }
}
