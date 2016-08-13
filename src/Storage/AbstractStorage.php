<?php

namespace Obullo\Auth\MFA\Storage;

/**
 * Abstract Adapter
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * Auth key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Identity lifetime
     *
     * @var int
     */
    protected $permanentBlockLifetime = 3600;

    /**
     * Temporary identity lifetime
     *
     * @var int
     */
    protected $temporaryBlockLifetime = 300;

    /**
     * Constructor
     *
     * @param array $options options
     */
    public function __construct($options = array())
    {
        $this->cacheKey = isset($options['cacheKey']) ? $options['cacheKey'] : 'Auth';
    }

    /**
     * Set permanent block lifetime
     *
     * @param int $value value
     */
    public function setPermanentBlockLifetime($value)
    {
        $this->permanentBlockLifetime = (int)$value;
    }

    /**
     * Set temporart block lifetime
     *
     * @param int $value value
     */
    public function setTemporaryBlockLifetime($value)
    {
        $this->temporaryBlockLifetime = (int)$value;
    }

    /**
     * Returns to permanent block lifetime
     *
     * @param int $value value
     */
    public function getPermanentBlockLifetime()
    {
        return $this->permanentBlockLifetime;
    }

    /**
     * Returns to temporary block lifetime
     *
     * @param int $value value
     */
    public function getTemporaryBlockLifetime()
    {
        return $this->temporaryBlockLifetime;
    }

    /**
     * Sets identifier value to session
     *
     * @param string $identifier user id
     *
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $_SESSION[$this->getCacheKey().'_Identifier'] = $identifier;
    }

    /**
     * Returns to user identifier
     *
     * @return mixed string|id
     */
    public function getIdentifier()
    {
        $key = $this->getCacheKey().'_Identifier';
        
        return empty($_SESSION[$key]) ? null : $_SESSION[$key].':'.$this->getLoginId();
    }

    /**
     * Unset identifier from session
     *
     * @return void
     */
    public function unsetIdentifier()
    {
        unset($_SESSION[$this->getCacheKey().'_Identifier']);
    }

    /**
     * Check user has identifier
     *
     * @return bool
     */
    public function hasIdentifier()
    {
        return ($this->getIdentifier() == null) ? false : true;
    }

    /**
     * Register credentials to temporary storage
     *
     * @param array $credentials user identities
     *
     * @return void
     */
    public function createTemporary(array $credentials)
    {
        $credentials['__isAuthenticated'] = 0;
        $credentials['__isTemporary'] = 1;
        $this->setCredentials($credentials, null, $this->getTemporaryBlockLifetime());
    }

    /**
     * Register credentials to permanent storage
     *
     * @param array $credentials user identities
     *
     * @return void
     */
    public function createPermanent(array $credentials)
    {
        $credentials['__isAuthenticated'] = 1;
        $credentials['__isTemporary'] = 0;
        $this->setCredentials($credentials, null, $this->getPermanentBlockLifetime());
    }

    /**
     * Makes temporary credentials as permanent and authenticate the user.
     *
     * @return mixed false|array
     */
    public function makePermanent()
    {
        if ($this->isEmpty()) {
            return false;
        }
        $credentials = $this->getCredentials();
        if ($credentials == false) {  // If already permanent
            return;
        }
        $credentials['__isAuthenticated'] = 1;
        $credentials['__isTemporary'] = 0;

        if ($this->setCredentials($credentials, null)) {
            return $credentials;
        }
        return false;
    }

    /**
     * Makes permanent credentials as temporary and unauthenticate the user.
     *
     * @return mixed false|array
     */
    public function makeTemporary($expire = null)
    {
        if ($this->isEmpty()) {
            return false;
        }
        if (is_numeric($expire)) {
            $this->setTemporaryBlockLifetime($expire);
        }
        $credentials = $this->getCredentials();
        if ($credentials == false) {  // If already permanent
            return;
        }
        $credentials['__isAuthenticated'] = 0;
        $credentials['__isTemporary'] = 1;

        if ($this->setCredentials($credentials, null)) {
            return $credentials;
        }
        return false;
    }

    /**
     * Get id of identifier without random Id value
     *
     * @return string|null
     */
    public function getUserId()
    {
        $key = $this->getCacheKey().'_Identifier';

        if (empty($_SESSION[$key])) {
            return null;
        }
        return $_SESSION[$key]; // user@example.com
    }

    /**
     * Get random id
     *
     * @return string
     */
    public function getLoginId()
    {
        $key = $this->getCacheKey().'_LoginId';

        if (empty($_SESSION[$key])) {
            return $this->setLoginId();
        }
        return $_SESSION[$key];
    }

    /**
     * Create login id
     *
     * @return string
     */
    public function setLoginId()
    {
        $client    = $this->request->getAttribute('Auth_Client');
        $userAgent = substr($client['HTTP_USER_AGENT'], 0, 50); // First 50 characters of the user agent
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);
        $id = md5(trim($userAgent).$microtime);
        $_SESSION[$this->getCacheKey().'_LoginId'] = $id;
        return $id;
    }

    /**
     * Unset login id of user
     *
     * @return void
     */
    public function unsetLoginId()
    {
        unset($_SESSION[$this->getCacheKey().'_LoginId']);
    }

    /**
     * Gey cache key
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Returns to storage full key of identity data
     *
     * @return string
     */
    public function getMemoryBlockKey()
    {
        /**
         * In here memcached like storages use $this->storage->getUserId()
         * but redis like storages use $this->storage->getIdentifier();
         */
        return $this->getCacheKey(). ':' .$this->getIdentifier();  // Create unique key
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
}
