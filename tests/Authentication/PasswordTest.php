<?php

use Obullo\Auth\Password;
use Obullo\Auth\WebTestCase;

class PasswordTest extends WebTestCase
{
    protected $password;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->password = $this->container->get('Auth:Password');
    }

    public function testVerify()
    {
        $result = $this->password->verify(
            '123456',
            '$2y$06$QRU3zQG0YnpDO8UW6ULATeTu0Z0wVF8fkozxoebPg8zu1LXXAwwf2'
        );
        $this->assertTrue($result, "I expect that the value is true");
    }
}
