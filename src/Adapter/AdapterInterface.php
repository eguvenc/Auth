<?php

namespace Obullo\Auth\MFA\Adapter;

use Obullo\Auth\MFA\CredentialsInterface as Credentials;

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
}
