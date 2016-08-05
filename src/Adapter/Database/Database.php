<?php

namespace Obullo\Authentication\Adapter\Database;

use Obullo\Authentication\AuthResult;
use Obullo\Authentication\Adapter\AbstractAdapter;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Obullo\Authentication\CredentialsInterface as Credentials;

/**
 * Database Adapter
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Database extends AbstractAdapter
{
    /**
     * Table
     *
     * @var object
     */
    protected $table;

    /**
     * Request
     *
     * @var object
     */
    protected $request;
    
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
     * Check temporary identity exists in storage
     *
     * @var boolean
     */
    protected $isTemporary = false;

    /**
     * Failure switch
     *
     * @var boolean
     */
    protected $failure = false;

    /**
     * Whether to regenerate session id after login
     *
     * @var boolean
     */
    protected $regenerateSessionId = true;

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
     * @param Request   $request   http server request
     */
    public function __construct(Container $container, Request $request)
    {
        $this->request   = $request;
        $this->container = $container;
        $this->table     = $container->get('Auth:Table');
        $this->storage   = $container->get('Auth:Storage');
        $this->identity  = $container->get('Auth:Identity');
    }

    /**
     * Set session regenerate id functionality
     *
     * @param boolean $enabled true or false
     */
    public function regenerateSessionId($enabled = true)
    {
        $this->regenerateSessionId = $enabled;
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
    public function login(Credentials $credentials)
    {
        $this->ignoreRecaller($credentials);  // Ignore recaller if user has remember cookie

        if ($this->checkCredentials($credentials) == false) {
            $message = 'Login attempt requires username and plain password values.';
            return new AuthResult(
                AuthResult::FAILURE,
                null,
                $message
            );
        }
        $this->initialize($credentials);
        $this->authenticate($credentials);  // Perform Query
        
        if (($authResult = $this->validateResultSet()) instanceof AuthResult) {
            return $authResult;  // If we have errors return to auth results.
        }
        $authResult = $this->validateResult();
        return $authResult;
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
        $this->ignoreRecaller($credentials);  // Ignore recaller if user has remember cookie

        return $this->authenticate($credentials, false);  // validate credentials
    }

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to "memory storage".
     * If memory login fail it will connect to "database table" and run sql
     * query to find a record matching the provided identity.
     *
     * @param array   $credentials username and plain password
     * @param array   $login       whether to generate user
     *
     * @return object
     */
    protected function authenticate(Credentials $credentials, $login = true)
    {
        $storageResult = $this->storage->query();  // if identity exists returns to cached data

        /**
         * If cached identity does not exist in memory do SQL query
         */
        $this->resultRowArray = ($storageResult === false) ? $this->table->query($credentials) : $storageResult;

        if (is_array($this->resultRowArray) && isset($this->resultRowArray[$this->table->getIdentityColumn()])) {
            $plain = $credentials->getPasswordValue();
            $hash  = $this->resultRowArray[$this->table->getPasswordColumn()];
            
            if ($this->verifyPassword($plain, $hash)) {
            // In here hash may cause performance bottleneck
            // depending to passwordNeedHash "cost" value default is 6
            // for best performance, set 10-12 for max security.

                if ($login) {  // If login process allowed.
                    $this->generateUser($credentials, $this->resultRowArray);
                }
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
     * @param array $credentials         username and plain password
     * @param array $resultRowArray      success auth query user data
     * @param array $passwordNeedsRehash marks attribute if password needs rehash
     *
     * @return object
     */
    protected function generateUser(Credentials $credentials, $resultRowArray)
    {
        $client = $this->request->getAttribute('Auth_Request');

        $attributes = array(
            $this->table->getIdentityColumn() => $credentials->getIdentityValue(),
            $this->table->getPasswordColumn() => $resultRowArray[$this->table->getPasswordColumn()],
            '__rememberMe' => $credentials->getRememberMeValue(),
            '__time' => time(),
            '__agent' => $client['HTTP_USER_AGENT'],
            '__ip' => $client['REMOTE_ADDR'],
        );
        /**
         * Authenticate the user and fornat auth data
         */
        $attributes = array_merge($resultRowArray, $attributes);

        if ($this->regenerateSessionId) {
            $this->regenerateSessionId(true); // Delete old session after regenerate !
        }
        if ($credentials->getRememberMeValue()) {  // If user choosed remember feature
            $token = $this->container->get('Auth:RememberMe')->getToken();
            $this->table->updateRememberToken($token, $credentials->getIdentityValue()); // refresh rememberToken
        }
        if ($this->storage->isEmpty('__temporary')) {  // If user has NOT got a temporay identity
            $this->storage->createPermanent($attributes);
        } else {
            $this->storage->createTemporary($attributes); // If user has a temporay identity go on as temporary.
        }
    }

    /**
     * This method attempts to make
     * certain that only one record was returned in the resultset
     *
     * @return bool|Obullo\Authentication\Result
     */
    protected function validateResultSet()
    {
        if (! $this->storage->isEmpty('__temporary')) {
            $this->results['code'] = AuthResult::TEMPORARY_AUTH;
            $this->results['messages'][] = 'Temporary auth has been created.';
            return $this->createResult();
        }
        return true;
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
     * Creates a Obullo\Authentication\AuthResult object from the information that
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
     * Verify password hash
     *
     * @param string $plain plain  password
     * @param string $hash  hashed password
     *
     * @return boolean | array
     */
    protected function verifyPassword($plain, $hash)
    {
        $cost = $this->container->get('Auth.PASSWORD_COST');
        $algo = $this->container->get('Auth.PASSWORD_ALGORITHM');

        if (password_verify($plain, $hash)) {
            if (password_needs_rehash($hash, $algo, array('cost' => $cost))) {
                $this->passwordHash = password_hash($plain, $algo, array('cost' => $cost));
            }
            return true;
        }
        return false;
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

    /**
     * Remove recaller cookie and ignore recaller functionality.
     *
     * @return void
     */
    protected function ignoreRecaller()
    {
        if ($this->container->get('Auth:RememberMe')->readToken()) {
            $_SESSION['Auth_IgnoreRecaller'] = 1;
        }
    }
}
