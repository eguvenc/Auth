<?php

namespace Obullo\Auth\Provider;

use Obullo\Auth\User\CredentialsInterface as Credentials;

/**
 * Auth Provider Interface
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface ProviderInterface
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
    public function recall($tokenValue);
    
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
