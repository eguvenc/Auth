<?php

namespace Obullo\Authentication;

use Interop\Container\ContainerInterface as Container;

use Obullo\Authentication\IdentityInterface as Identity;
use Obullo\Authentication\Storage\StorageInterface as Storage;
use Obullo\Authentication\Adapter\Datababase\TableInterface as Table;

/**
 * Recaller
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Recaller
{
    /**
     * Table
     *
     * @var object
     */
    protected $table;

    /**
     * Storage
     *
     * @var object
     */
    protected $storage;

    /**
     * User identity
     *
     * @var object
     */
    protected $identity;

    /**
     * Container
     *
     * @var object
     */
    protected $container;

    /**
     * Constructor
     *
     * @param object $storage   auth storage
     * @param object $table     auth table
     * @param array  $identity  auth identity
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->table     = $container->get('Auth:Table');
        $this->storage   = $container->get('Auth:Storage');
        $this->identity  = $container->get('Auth:Identity');
    }

    /**
     * Recall user identity using remember token
     *
     * @param string $token recaller token
     *
     * @return void
     */
    public function recallUser($tokenValue)
    {
        $resultRowArray = $this->table->recall($tokenValue);

        $identityColumn      = $this->table->getIdentityColumn();
        $rememberTokenColumn = $this->table->getRememberTokenColumn();

        if (! is_array($resultRowArray) || empty($resultRowArray[$rememberTokenColumn])) {
            $this->storage->setIdentifier('Guest');   // Mark user as guest
            $this->identity->forgetMe();
            return;
        }
        $this->storage->setIdentifier($resultRowArray[$identityColumn]);

        $data = [
            $identityColumn => $resultRowArray[$identityColumn],
            '__rememberMe' => 1,
            '__rememberToken' => $resultRowArray[$rememberTokenColumn]
        ];
        $this->storage->setCredentials($data, null);

        /**
         * Generate authenticated user
         */
        $this->container->get('Auth:Adapter')->generateUser($data, $resultRowArray);
        
        $this->removeInactiveSessions(); // Kill all inactive sessions of current user
    }

    /**
     * Destroy all inactive sessions of the user
     *
     * @return void
     */
    protected function removeInactiveSessions()
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
