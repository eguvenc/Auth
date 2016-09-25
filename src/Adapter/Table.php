<?php

namespace Obullo\Auth\Adapter;

use Obullo\Auth\User\User;
use Obullo\Auth\AuthResult;
use Obullo\Auth\User\UserInterface;
use Obullo\Auth\Adapter\AbstractAdapter;
use Interop\Container\ContainerInterface as Container;
use Obullo\Auth\User\CredentialsInterface as Credentials;

/**
 * Database Table Adapter
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Table extends AbstractAdapter
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
     * Identity
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
     * Result messages
     *
     * @var array
     */
    protected $results = null;

    /**
     * Results of authentication query
     *
     * @var array
     */
    protected $resultRowArray = array();
    
    /**
     * Failure switch
     *
     * @var boolean
     */
    protected $failure = false;

    /**
     * Password needs rehash value
     *
     * @var string
     */
    protected $passwordHash;

    /**
     * Constructor
     *
     * @param Container $container container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->table     = $container->get('Auth:Table');
        $this->storage   = $container->get('Auth:Storage');
        $this->identity  = $container->get('Auth:Identity');
    }

    /**
     * Creates array data before authenticate
     *
     * @param array $credentials username and plain password
     *
     * @return boolean if success
     */
    protected function initialize(Credentials $credentials)
    {
        $savedIdentifier = $this->storage->getUserId();
        $identifier      = $credentials->getIdentityValue();

        if ($this->identity->guest() || $savedIdentifier != $identifier) {
            $this->storage->setIdentifier($identifier); // Set current identifier to storage
        }
        $this->results = array(
            'code' => AuthResult::FAILURE,
            'identity' => $identifier,
            'messages' => array()
        );
        return true;
    }

    /**
     * Performs an authentication attempt
     *
     * @param array $credentials username and plain password
     *
     * @return object authResult
     */
    public function authenticate(Credentials $credentials)
    {
        if ($this->checkCredentials($credentials) == false) {
            $message = 'Authentication requires username and plain password.';
            return new AuthResult(
                AuthResult::FAILURE,
                null,
                $message
            );
        }
        $this->initialize($credentials);
        $this->authenticationRequest($credentials);  // Perform Query

        return $this->validateResult();
    }

    /**
     * Validate user credentials without login
     *
     * @param  Credentials $credentials object
     *
     * @return boolean returns false if validation failed
     */
    public function validateCredentials(Credentials $credentials)
    {
        return $this->authenticationRequest($credentials);  // validate credentials
    }

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to "memory storage".
     * If memory login fail it will connect to "database table" and run sql
     * query to find a record matching the provided identity.
     *
     * @param array $credentials username and plain password
     *
     * @return object
     */
    protected function authenticationRequest(Credentials $credentials)
    {
        $storageResult = $this->storage->query();  // if identity exists returns to cached data
        /**
         * If cached identity does not exist in memory do SQL query
         */
        $this->resultRowArray = ($storageResult === false) ? $this->table->query($credentials) : $storageResult;

        $id   = $this->table->getIdentityColumn();
        $pass = $this->table->getPasswordColumn();

        if (is_array($this->resultRowArray) && isset($this->resultRowArray[$id])) {
            $plain = $credentials->getPasswordValue();
            $hash  = $this->resultRowArray[$pass];
            
            if ($this->passwordHash = $this->container->get('Auth:Password')->verify($plain, $hash)) {
            // In here hash may cause performance bottleneck
            // depending to passwordNeedHash "cost" value default is 6
            // for best performance, set 10-12 for max security.
                return true;
            }
        }
        $this->resultRowArray = array();
        $this->failure = true; // We set failure variable when user password is fail.
        return false;
    }

    /**
     * Set identities data to AuthorizedUser object
     *
     * @param object User $user
     *
     * @return object of identity
     */
    public function authorize(UserInterface $user)
    {
        $credentials = $user->getCredentials();
        $resultRow   = $user->getResultRow();

        $attributes = array(
            $this->table->getIdentityColumn() => $credentials->getIdentityValue(),
            $this->table->getPasswordColumn() => $resultRow[$this->table->getPasswordColumn()],
            '__rememberMe' => $credentials->getRememberMeValue(),
            '__time' => $this->getMicrotime(),
            '__agent' => $this->container->get('Auth.HTTP_USER_AGENT')->getValue(),
            '__ip' => $this->container->get('Auth.REMOTE_ADDR')->getValue(),
        );
        /**
         * Fornat auth data
         */
        $attributes = array_merge($resultRow, $attributes);

        if ($credentials->getRememberMeValue()) {  // If user choosed remember feature
            $token = $this->container->get('Auth:RecallerToken')->create();
            $this->table->updateRememberToken($token, $credentials->getIdentityValue()); // refresh rememberToken
        }
        if ($this->identity->isTemporary()) {
            $this->storage->createTemporary($attributes); // If user has a temporay identity go on as temporary.
        } else {
            $this->storage->createPermanent($attributes); // If user has NOT got a temporay identity
        }
        return $this->identity;
    }

    /**
     * This method attempts to validate that
     * the record in the resultset is indeed a record that matched the
     * identity provided to this adapter.
     *
     * @return AuthResult
     */
    protected function validateResult()
    {
        if (! is_array($this->resultRowArray) || $this->failure) {   // We set failure variable when user password is fail.
            $this->results['code'] = AuthResult::FAILURE;
            $this->results['messages'][] = 'Supplied credential is invalid.';
            return $this->createResult();
        }
        if (sizeof($this->resultRowArray) == 0) {
            $this->results['code'] = AuthResult::FAILURE_CREDENTIAL_INVALID;
            $this->results['messages'][] = 'Supplied credential is invalid.';
            return $this->createResult();
        }
        if (isset($this->resultRowArray[1][$this->table->getIdentityColumn()])) {
            $this->results['code'] = AuthResult::FAILURE_IDENTITY_AMBIGUOUS;
            $this->results['messages'][] = 'More than one record matches the supplied identity.';
            return $this->createResult();
        }
        $this->results['code'] = AuthResult::SUCCESS;
        $this->results['messages'][] = 'Authentication successful.';
        return $this->createResult();
    }

    /**
     * Creates a Obullo\Auth\AuthResult object from the information that
     * has been collected during the authenticate() attempt.
     *
     * @return AuthResult
     */
    protected function createResult()
    {
        $result = new AuthResult(
            $this->results['code'],
            $this->results['identity'],
            $this->results['messages']
        );
        $result->setResultRow($this->resultRowArray);
        return $result;
    }

    /**
     * Returns to rehashed password if needs rehash
     *
     * @return string
     */
    public function passwordNeedsRehash()
    {
        return $this->passwordHash;
    }
}
