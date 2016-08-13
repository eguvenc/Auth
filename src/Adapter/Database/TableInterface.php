<?php

namespace Obullo\Auth\MFA\Adapter\Database;

use Obullo\Auth\MFA\CredentialsInterface as Credentials;

/**
 * Auth Table Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface TableInterface
{
    /**
     * Execute query
     *
     * @param array $credentials user credentials
     *
     * @return mixed boolean|array
     */
    public function query(Credentials $credentials);
    
    /**
     * Execute recaller query
     *
     * @param string $token rememberMe token
     *
     * @return array
     */
    public function recall($token);
    
    /**
     * Update remember token upon every login & logout requests
     *
     * @param string $tokenValue    token value
     * @param array  $identityValue identity value
     *
     * @return void
     */
    public function updateRememberToken($tokenValue, $identityValue);
}
