<?php

use Obullo\Auth\User\Credentials;

class CredentialsTest extends WebTestCase
{
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
    }

    public function testSetIdentityValue()
    {
        $value = $this->credentials->getIdentityValue();

        $this->assertEquals($value, 'user@example.com', "I expect that the value is equal to 'user@example.com'");
    }

    public function testSetPasswordValue()
    {
        $this->credentials->setPasswordValue('654321');
        $value = $this->credentials->getPasswordValue();

        $this->assertEquals($value, '654321', "I expect that the value is equal to '654321'");
    }

    public function testSetRememberMeValue()
    {
        $this->credentials->setRememberMeValue(false);
        $value = $this->credentials->getRememberMeValue();

        $this->assertInternalType('int', $value);
        $this->assertEquals($value, 0, "I expect that the value is 0");

        $this->credentials->setRememberMeValue(true);
        $value = $this->credentials->getRememberMeValue();
        
        $this->assertInternalType('int', $value);
        $this->assertEquals($value, 1, "I expect that the value is 1");
    }
}
