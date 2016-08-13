<?php

namespace Obullo\Auth\MFA;

/**
 * Credentials Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface CredentialsInterface
{
    /**
     * Set identity value
     *
     * @param string $identity value
     */
    public function setIdentityValue($identity);

    /**
     * Set password value
     *
     * @param string $password password value
     */
    public function setPasswordValue($password);
    
    /**
     * Set remember me value
     *
     * @param string $value remember value
     */
    public function setRememberMeValue($value);

    /**
     * Get identity value
     *
     * @return string
     */
    public function getIdentityValue();

    /**
     * Get password value
     *
     * @return string
     */
    public function getPasswordValue();

    /**
     * Get remember me value
     *
     * @return integer
     */
    public function getRememberMeValue();
}
