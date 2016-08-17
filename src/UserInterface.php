<?php

namespace Obullo\Auth\MFA;

use Obullo\Auth\MFA\CredentialsInterface as Credentials;

/**
 * User Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface UserInterface
{
    /**
     * Set user credentials
     *
     * @param Credentials $credentials credentials
     */
    public function setCredentials(Credentials $credentials);

    /**
     * Returns to credentials object
     *
     * @return object credentials
     */
    public function getCredentials();

    /**
     * Set query result row
     *
     * @param array $resultRowArray query result row
     */
    public function setResultRow(array $resultRowArray);

    /**
     * Returns to query result row
     *
     * @return array query result row
     */
    public function getResultRow();
}
