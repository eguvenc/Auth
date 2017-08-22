<?php

namespace Obullo\Auth;

use Interop\Container\ContainerInterface as Container;

/**
 * Recaller
 *
 * @copyright 2017 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Recaller
{
    /**
     * Provider
     *
     * @var object
     */
    protected $provider;

    /**
     * Storage
     *
     * @var object
     */
    protected $storage;

    /**
     * Container
     *
     * @var object
     */
    protected $container;

    /**
     * User query result data
     *
     * @var array
     */
    protected $resultRowArray;

    /**
     * Constructor
     *
     * @param object Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->provider  = $container->get('Auth:Provider');
        $this->storage   = $container->get('Auth:Storage');
    }

    /**
     * Recall user identity using remember token
     *
     * @param string $token recaller token
     *
     * @return array|false
     */
    public function recallUser($tokenValue)
    {
        $resultRowArray = $this->provider->recall($tokenValue);

        $identityColumn      = $this->provider->getIdentityColumn();
        $rememberTokenColumn = $this->provider->getRememberTokenColumn();

        if (! is_array($resultRowArray) || empty($resultRowArray[$rememberTokenColumn])) {
            $this->storage->setIdentifier('Guest');   // Mark user as guest
            $this->container->get('Auth:RecallerToken')->remove();
            return false;
        }
        $this->storage->setIdentifier($resultRowArray[$identityColumn]);

        $data = [
            $identityColumn => $resultRowArray[$identityColumn],
            '__rememberMe' => 1,
            '__rememberToken' => $resultRowArray[$rememberTokenColumn],
            '__isTemporary' => 0
        ];
        $this->storage->setCredentials($data, null);
        return $this->resultRowArray = $resultRowArray;
    }

    /**
     * Returns to user row data
     *
     * @return array
     */
    public function getResultRow()
    {
        return $this->resultRowArray;
    }

    /**
     * Destroy all inactive sessions of the user
     *
     * @return void
     */
    public function __destruct()
    {
        $sessions = $this->storage->getUserSessions();

        if (sizeof($sessions) == 0) {
            return;
        }
        foreach ($sessions as $loginID => $val) {       // Destroy all inactive sessions
            if (isset($val['__isAuthenticated']) && $val['__isAuthenticated'] == 0) {
                $this->storage->killSession($loginID);
            }
        }
    }
}
