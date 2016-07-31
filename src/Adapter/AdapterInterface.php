<?php

namespace Obullo\Authentication\Adapter;

use Obullo\Authentication\CredentialsInterface as Credentials;

/**
 * Adapter Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface AdapterInterface
{
    /**
     * Performs an authentication attempt
     *
     * @param object $credentials username and plain password
     *
     * @return object authResult
     */
    public function login(Credentials $credentials);

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to "memory storage".
     * If memory login fail it will connect to "database table" and run sql
     * query to find a record matching the provided identity.
     *
     * @param object $credentials username and plain password object
     *
     * @return object
     */
    public function authenticate(Credentials $credentials);
}
