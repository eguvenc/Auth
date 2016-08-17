<?php

namespace Obullo\Auth\MFA;

use Obullo\Auth\MFA\CredentialsInterface as Credentials;

/**
 * Authenticated User
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class User implements UserInterface
{
    /**
     * Credentials
     *
     * @var object
     */
    protected $credentials;

    /**
     * Query Result Row
     *
     * @var array
     */
    protected $resultRowArray;

    /**
     * Constructor
     *
     * @param Credentials $credentials credentials
     */
    public function __construct(Credentials $credentials)
    {
        $this->setCredentials($credentials);
    }

    /**
     * Set user credentials
     *
     * @param Credentials $credentials credentials
     */
    public function setCredentials(Credentials $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Returns to credentils object
     *
     * @return object credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Set query result row
     *
     * @param array $resultRowArray query result row
     */
    public function setResultRow(array $resultRowArray)
    {
        $this->resultRowArray = $resultRowArray;
    }

    /**
     * Returns to query result row
     *
     * @return array query result row
     */
    public function getResultRow()
    {
        return $this->resultRowArray;
    }
}
