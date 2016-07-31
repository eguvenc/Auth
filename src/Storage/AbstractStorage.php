<?php

namespace Obullo\Authentication\Storage;

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
        $this->cacheKey  = isset($options['cacheKey']) ? $options['cacheKey'] : 'Auth';
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
     * @return int
     */
    public function getMemoryBlockLifetime($block = '__permanent')
    {
        $var = substr($block, 2).'BlockLifetime';

        return $this->{$var};
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
        $this->setCredentials($credentials, null, '__temporary', $this->getMemoryBlockLifetime('__temporary'));
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
        $this->setCredentials($credentials, null, '__permanent', $this->getMemoryBlockLifetime('__permanent'));
    }

    /**
     * Makes temporary credentials as permanent and authenticate the user.
     *
     * @return mixed false|array
     */
    public function makePermanent()
    {
        if ($this->isEmpty('__temporary')) {
            return false;
        }
        $credentials = $this->getCredentials('__temporary');
        if ($credentials == false) {  // If already permanent
            return;
        }
        $credentials['__isAuthenticated'] = 1;
        $credentials['__isTemporary'] = 0;

        if ($this->setCredentials($credentials, null, '__permanent')) {
            $this->deleteCredentials('__temporary');
            return $credentials;
        }
        return false;
    }

    /**
     * Makes permanent credentials as temporary and unauthenticate the user.
     *
     * @return mixed false|array
     */
    public function makeTemporary()
    {
        if ($this->isEmpty('__permanent')) {
            return false;
        }
        $credentials = $this->getCredentials('__permanent');
        if ($credentials == false) {  // If already permanent
            return;
        }
        $credentials['__isAuthenticated'] = 0;
        $credentials['__isTemporary'] = 1;

        if ($this->setCredentials($credentials, null, '__temporary')) {
            $this->deleteCredentials('__permanent');
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
        $server = $this->request->getServerParams();

        $agentStr  = isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : null;
        $userAgent = substr($agentStr, 0, 50);  // First 50 characters of the user agent
        $loginId   = md5(trim($userAgent).time());

        $_SESSION[$this->getCacheKey().'_LoginId'] = $loginId;
        return $loginId;
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
     * Get valid memory segment key
     *
     * @param string $block name
     *
     * @return string
     */
    public function getBlock($block)
    {
        return ($block == '__temporary' || $block == '__permanent') ? $this->getMemoryBlockKey($block) : $block;
    }

    /**
     * Returns to storage full key of identity data
     *
     * @param string $block name
     *
     * @return string
     */
    public function getMemoryBlockKey($block = '__temporary')
    {
        return $this->getCacheKey(). ':' .$block. ':' .$this->getIdentifier();  // Create unique key
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
}
