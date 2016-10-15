<?php

use Obullo\Auth\User\User;
use Obullo\Auth\WebTestCase;
use Obullo\Auth\Adapter\Table;
use Obullo\Auth\User\Credentials;

class TableTest extends WebTestCase
{
    protected $db;
    protected $adapter;
    protected $credentials;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->credentials = new Credentials;
        $this->credentials->setIdentityValue('user@example.com');
        $this->credentials->setPasswordValue('123456');

        $this->adapter = new Table($this->container);
    }

    public function testAuthenticate()
    {
        $authResult = $this->adapter->authenticate($this->credentials);

        $this->assertTrue($authResult->isValid(), "I expect that the auth validation is true.");
    }

    public function testValidateCredentials()
    {
        $isValid = $this->adapter->validateCredentials($this->credentials);  // validate credentials

        $this->assertTrue($isValid, "I expect that the credentials validation is true.");
    }

    public function testAuthorize()
    {
        $authResult = $this->adapter->authenticate($this->credentials);

        $user = new User($this->credentials);
        $user->setResultRow($authResult->getResultRow());

        $identity = $this->adapter->authorize($user);
        $this->assertTrue($identity->check(), "I expect that the authorization result is true.");
        $identity->destroy();
    }

    public function testCheckCredentials()
    {
        $isValid = $this->adapter->checkCredentials($this->credentials);

        $this->assertTrue($isValid, "I expect that the credentials validation is true.");
    }
}
