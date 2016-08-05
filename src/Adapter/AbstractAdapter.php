<?php

namespace Obullo\Authentication\Adapter;

use Obullo\Authentication\CredentialsInterface as Credentials;

/**
 * Abstract Adapater
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Combine credentials with real column names
     *
     * @param array $credentials id & password data
     *
     * @return boolean
     */
    public function checkCredentials(Credentials $credentials)
    {
        $identity = $credentials->getIdentityValue();
        $password = $credentials->getPasswordValue();

        if (empty($identity) || empty($password)) {
            return false;
        }
        return true;
    }

    /**
     * Regenerate the session id
     *
     * @param bool $deleteOldSession whether to delete old session id
     *
     * @return string session_id
     */
    public function regenerateSessionId($deleteOldSession = true)
    {
        session_regenerate_id((bool) $deleteOldSession);

        return session_id(); // new session_id
    }

    /**
     * This method attempts to make
     * certain that only one record was returned in the resultset
     *
     * @return bool|Obullo\Authentication\Result
     */
    abstract protected function validateResultSet();

    /**
     * This method attempts to validate that
     * the record in the resultset is indeed a record that matched the
     * identity provided to this adapter.
     *
     * @return AuthResult
     */
    abstract protected function validateResult();
}
