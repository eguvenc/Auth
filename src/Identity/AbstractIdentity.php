<?php

namespace Obullo\Auth\MFA\Identity;

/**
 * Abstract Identity
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
abstract class AbstractIdentity implements IdentityInterface
{
    /**
     * Get the identifier column value
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        $id = $this->table->getIdentityColumn();

        return $this->get($id);
    }

    /**
     * Get the password column value
     *
     * @return mixed
     */
    public function getPassword()
    {
        $password = $this->table->getPasswordColumn();

        return $this->get($password);
    }

    /**
     * Returns to "1" user if used remember me
     *
     * @return integer
     */
    public function getRememberMe()
    {
        $rememberMe = $this->get('__rememberMe');

        return $rememberMe ? (int)$rememberMe : 0;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getArray()
    {
        return $this->storage->getCredentials();
    }

    /**
     * Get a value from identity data.
     *
     * @param string $key key
     *
     * @return mixed
     */
    public function get($key)
    {
        $attributes = $this->getArray();

        // if (! isset($attributes['__isAuthenticated']) || $attributes['__isAuthenticated'] < 1) {
        //     return false;
        // }
        return isset($attributes[$key]) ? $attributes[$key] : false;
    }

    /**
     * Set a value to identity data.
     *
     * @param string $key key
     * @param string $val value
     *
     * @return mixed
     */
    public function set($key, $val)
    {
        if ($this->get('__isAuthenticated') == 1) {   // Check user has auth
            $this->storage->update($key, $val);       // then accept update operation
        }
        if ($this->get('__isTemporary')) {
            $this->storage->updateTemporary($key, $val);
        }
    }

    /**
     * Remove a value from identity data.
     *
     * @param string $key key
     *
     * @return void
     */
    public function remove($key)
    {
        if ($this->get('__isAuthenticated') == 1) {
            $this->storage->remove($key);
        }
    }
}
