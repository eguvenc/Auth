<?php

namespace Obullo\Authentication\Identity;

use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * User Identity
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Identity extends AbstractIdentity
{
    /**
     * Memory block
     *
     * @var string
     */
    protected $block;

    /**
     * Storage
     *
     * @var object
     */
    protected $storage;

     /**
     * Container
     *
     * @var object
     */
    protected $container;

    /**
     * Constructor
     *
     * @param object $container container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->storage   = $container->get('auth:storage');
        $this->initialize();
        
        if ($tokenValue = $this->recallerExists()) {   // Remember the user if recaller cookie exists
            $recaller = new Recaller($container);
            $recaller->recallUser($tokenValue);
            $this->initialize();  // We need initialize again otherwise ignoreRecaller() does not work.
        }
    }

    /**
     * Initializer
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->storage->getCredentials('__permanent')) {
            $this->block = '__permanent';
            /**
             * We need extend the cache TTL of current user,
             * thats why we need update last activity for each page request.
             * Otherwise permanent storage TTL will be expired because of user has no activity.
             */
            $this->storage->update('__lastActivity', time());
            return;
        }
        $this->block = '__temporary';
    }

    /**
     * Check user has identity
     *
     * Its ok if returns to true otherwise false
     *
     * @return boolean
     */
    public function check()
    {
        if ($this->get('__isAuthenticated') == 1) {
            return true;
        }
        return false;
    }

    /**
     * Opposite of check() function
     *
     * @return boolean
     */
    public function guest()
    {
        if ($this->check()) {
            return false;
        }
        return true;
    }

    /**
     * Check recaller cookie exists
     *
     * WARNING : To test this function remove "Auth/Identifier" value from session
     * or use "$this->user->identity->destroy()" method.
     *
     * @return string|boolean false
     */
    public function recallerExists()
    {
        if (! empty($_SESSION['Auth_IgnoreRecaller']) && $_SESSION['Auth_IgnoreRecaller'] == 1) {
            unset($_SESSION['Auth_IgnoreRecaller']);
            return false;
        }
        $token = $this->container->get('auth:rememberMe')->readToken();

        // Check recaller cookie value is alfanumeric

        if ($this->guest() && ctype_alnum($token) && strlen($token) == 32) {
            return $token;
        }
        return false;
    }

    /**
     * Returns to 1 if user authenticated on temporary memory block otherwise 0.
     *
     * @return boolean
     */
    public function isTemporary()
    {
        return (bool)$this->get('__isTemporary');
    }

    /**
     * Set time to live
     *
     * @param int $ttl expire
     *
     * @return void
     */
    public function expire($ttl)
    {
        if ($this->check()) {
            $this->storage->update('__expire', time() + $ttl);
        }
    }

    /**
     * Check identity is expired
     *
     * @return boolean
     */
    public function isExpired()
    {
        if ($this->has('__expire') && $this->get('__expire') < time()) {
            return true;
        }
        return false;
    }

    /**
     * Move permanent identity to temporary block
     *
     * @return void
     */
    public function makeTemporary()
    {
        $this->storage->makeTemporary();
        $this->block = '__temporary';
    }

    /**
     * Move temporary identity to permanent block
     *
     * @return void
     */
    public function makePermanent()
    {
        $this->storage->makePermanent();
        $this->block = '__permanent';
    }

    /**
     * Checks new identity data available in storage.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->get('__isAuthenticated') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns to unix microtime value.
     *
     * @return string
     */
    public function getTime()
    {
        return (int)$this->get('__time');
    }

    /**
     * Get the password needs rehash array.
     *
     * @return boolean
     */
    public function getPasswordNeedsReHash()
    {
        $passwordNeedsRehash = $this->get('__passwordNeedsRehash');

        return $passwordNeedsRehash ? true : false;
    }

    /**
     * Returns to remember token
     *
     * @return integer
     */
    public function getRememberToken()
    {
        return $this->get($this->table->getRememberTokenColumn());
    }

    /**
     * Returns to login id of user, its an unique id for each browsers e.g: 87060e89.
     *
     * @return string|false
     */
    public function getLoginId()
    {
        if (! $this->exists()) {
            return false;
        }
        return empty($_SESSION['Auth_LoginId']) ? false : $_SESSION['Auth_LoginId'];
    }

    /**
     * Sets authority of user to "0" don't touch to cached data
     *
     * @return void
     */
    public function logout()
    {
        if ($this->check()) {
            $this->updateRememberToken();
            $this->storage->update('__isAuthenticated', 0);
            
            // Do not remove identifier otherwise we can't get
            // user data using $this->user->identity->getArray().
        }
    }

    /**
     * Destroy permanent identity
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->guest()) {
            return;
        }
        $this->updateRememberToken();
        $this->storage->deleteCredentials('__permanent');
        $this->removeSessionIdentifiers();
    }

    /**
     * Remove identifiers from session
     *
     * @return void
     */
    protected function removeSessionIdentifiers()
    {
        unset($_SESSION['Auth_LoginId']);
        unset($_SESSION['Auth_Identifier']);
    }

    /**
     * Update temporary credentials
     *
     * @param string $key key
     * @param string $val value
     *
     * @return void
     */
    public function updateTemporary($key, $val)
    {
        if ($this->check()) {
            return;
        }
        $this->storage->update($key, $val, '__temporary');
    }

    /**
     * Destroy temporary identity of unauthorized user
     *
     * @return void
     */
    public function destroyTemporary()
    {
        if ($this->check()) {
            return;
        }
        $this->updateRememberToken();
        $this->storage->deleteCredentials('__temporary');
        $this->removeSessionIdentifiers();
    }

    /**
     * Update remember token if it exists in the memory and browser header
     *
     * @return int|boolean
     */
    public function updateRememberToken()
    {
        if ($this->getRememberMe() == 1) {  // If user checked rememberMe option

            $tokenValue = $this->refreshRememberToken();
            $this->set($this->container->get('auth:table')->getRememberTokenColumn(), $tokenValue);
            return;
        }
    }

    /**
     * Refresh the rememberMe token
     *
     * @return string
     */
    public function refreshRememberToken()
    {
        $tokenValue = $this->container->get('auth:rememberMe')->getToken();
        $this->container->get('auth:table')->updateRememberToken($tokenValue, $this->getIdentifier());

        return $tokenValue;
    }

    /**
     * Removes rememberMe cookie from user browser
     *
     * @return void
     */
    public function forgetMe()
    {
        $this->container->get('auth:rememberMe')->removeToken();
    }

    /**
     * Kill authority of user using auth id
     *
     * @param integer $loginId e.g: 87060e89
     *
     * @return boolean
     */
    public function kill($loginId)
    {
        $this->storage->killSession($loginId);
    }
}
