<?php

namespace Obullo\Auth\MFA;

use Interop\Container\ContainerInterface as Container;
use Obullo\Auth\MFA\Storage\StorageInterface as Storage;
use Obullo\Auth\MFA\Identity\IdentityInterface as Identity;
use Obullo\Auth\MFA\Adapter\Datababase\TableInterface as Table;

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
     * Container
     *
     * @var object
     */
    protected $container;

    /**
     * Constructor
     *
     * @param object Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->table     = $container->get('Auth:Table');
        $this->storage   = $container->get('Auth:Storage');
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
        $passwordColumn      = $this->table->getPasswordColumn();
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
            '__rememberToken' => $resultRowArray[$rememberTokenColumn],
            '__isTemporary' => 0
        ];
        $this->storage->setCredentials($data, null);
        
        $credentials = new Credentials;
        $credentials->setIdentityValue($resultRowArray[$identityColumn]);
        $credentials->setPasswordValue($resultRowArray[$passwordColumn]);
        $credentials->setRememberMeValue(true);

        $user = new User($credentials);
        $user->setResultRow($resultRowArray);
        return $user;
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
