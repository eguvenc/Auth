<?php

namespace Obullo\Auth\MFA\User;

/**
 * RememberMe Interface
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface RememberMeInterface
{
    /**
     * Read recaller token
     *
     * @return string
     */
    public function readToken();
    
    /**
     * Create recaller token
     *
     * @return string token
     */
    public function getToken();

    /**
     * Remove recaller token
     *
     * @return void
     */
    public function removeToken();
}
