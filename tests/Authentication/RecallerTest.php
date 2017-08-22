<?php

use Obullo\Auth\Recaller;
use Obullo\Auth\WebTestCase;

class RecallerTest extends WebTestCase
{
    protected $provider;
    protected $password;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->provider = $this->container->get('Auth:Provider');
        $this->recaller = new Recaller($this->container);
    }

    public function testRecallUser()
    {
        $token = 'T1KPlDSWFZjG1v5BYHifEfmwzZoWd0A4';
        $this->provider->updateRememberToken($token, 'user@example.com');

        $resultRow = $this->recaller->recallUser($token);

        if ($this->assertArrayHasKey('username', $resultRow)) {
            $this->assertEquals($resultRow['username'], 'test');
        }
        if ($this->assertArrayHasKey('email', $resultRow)) {
            $this->assertEquals($resultRow['email'], 'user@example.com');
        }
        if ($this->assertArrayHasKey('remember_token', $resultRow)) {
            $this->assertEquals($resultRow['remember_token'], 'T1KPlDSWFZjG1v5BYHifEfmwzZoWd0A4');
        }
    }

    public function testGetResultRow()
    {
        $token = 'T1KPlDSWFZjG1v5BYHifEfmwzZoWd0A4';
        $this->provider->updateRememberToken($token, 'user@example.com');

        $this->recaller->recallUser($token);
        $resultRow = $this->recaller->getResultRow();

        if ($this->assertArrayHasKey('username', $resultRow)) {
            $this->assertEquals($resultRow['username'], 'test');
        }
        if ($this->assertArrayHasKey('email', $resultRow)) {
            $this->assertEquals($resultRow['email'], 'user@example.com');
        }
        if ($this->assertArrayHasKey('remember_token', $resultRow)) {
            $this->assertEquals($resultRow['remember_token'], 'T1KPlDSWFZjG1v5BYHifEfmwzZoWd0A4');
        }
    }
}
