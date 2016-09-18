<?php

namespace Obullo\Auth\User;

/**
 * User Credentials
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Credentials implements CredentialsInterface
{
    /**
     * Identity value
     *
     * @var string
     */
    protected $identity;

    /**
     * Password value
     *
     * @var string
     */
    protected $password;

    /**
     * Remember me option
     *
     * @var int
     */
    protected $rememberMe = 0;

    /**
     * Set identity value
     *
     * @param string $identity value
     */
    public function setIdentityValue($identity)
    {
        $this->identity = $identity;
    }

    /**
     * Set password value
     *
     * @param string $password password value
     */
    public function setPasswordValue($password)
    {
        $this->password = $password;
    }
    
    /**
     * Set remember me value
     *
     * @param string $value remember value
     */
    public function setRememberMeValue($value)
    {
        $this->rememberMe = (int)$value;
    }

    /**
     * Get identity value
     *
     * @return string
     */
    public function getIdentityValue()
    {
        return $this->identity;
    }

    /**
     * Get password value
     *
     * @return string
     */
    public function getPasswordValue()
    {
        return $this->password;
    }

    /**
     * Get remember me value
     *
     * @return integer
     */
    public function getRememberMeValue()
    {
        return $this->rememberMe;
    }
}
