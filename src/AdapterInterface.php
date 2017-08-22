<?php

namespace Obullo\Auth;

use Obullo\Auth\UserInterface as User;
use Obullo\Auth\CredentialsInterface as Credentials;

/**
 * Adapter Interface
 *
 * @copyright 2016 Obullo
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
    public function authenticate(Credentials $credentials);

    /**
     * Authorize user
     *
     * @param User $user user
     *
     * @return void
     */
    public function authorize(User $user);
}
