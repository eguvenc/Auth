<?php

use Obullo\Auth\User\User;
use Obullo\Auth\WebTestCase;
use Obullo\Auth\User\Credentials;

class UserTest extends WebTestCase
{
    protected $user;
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

        $this->user = new User($this->credentials);
    }

    public function testSetCredentials()
    {
        $this->user->setCredentials($this->credentials);
        $credentials = $this->user->getCredentials();

        $this->assertEquals($credentials->getIdentityValue(), 'user@example.com', "I expect that the value is 'user@example.com'");
        $this->assertEquals($credentials->getPasswordValue(), '123456', "I expect that the value is '123456'");
    }

    public function testSetResultRow()
    {
        $this->user->setResultRow(array(
            'username' => 'test',
            'password' => '$2y$06$QRU3zQG0YnpDO8UW6ULATeTu0Z0wVF8fkozxoebPg8zu1LXXAwwf2',
            'email' => 'user@example.com',
            'remember_token' => 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw'
        ));
        $resultArray = $this->user->getResultRow();

        $this->assertEquals($resultArray['username'], 'test');
        $this->assertEquals($resultArray['email'], 'user@example.com');
        $this->assertEquals($resultArray['remember_token'], 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw');
        $this->assertEquals(
            $resultArray['password'],
            '$2y$06$QRU3zQG0YnpDO8UW6ULATeTu0Z0wVF8fkozxoebPg8zu1LXXAwwf2'
        );
    }

}
