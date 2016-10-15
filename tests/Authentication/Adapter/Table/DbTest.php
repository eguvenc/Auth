<?php

use Obullo\Auth\WebTestCase;
use Obullo\Auth\Adapter\Table\Db;
use Obullo\Auth\User\Credentials;

class DbTest extends WebTestCase
{
    protected $dbTable;
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
        $this->credentials->setIdentityValue("user@example.com");
        $this->credentials->setPasswordValue("123456");

        $conn = $this->container->get('database:default');
        
        $this->dbTable = new Db($conn);
        $this->dbTable->setColumns(array('username', 'password', 'email', 'remember_token'));
        $this->dbTable->setTableName('users');
        $this->dbTable->setIdentityColumn('email');
        $this->dbTable->setPasswordColumn('password');
        $this->dbTable->setRememberTokenColumn('remember_token');
    }

    public function testQuery()
    {
        $resultArray = $this->dbTable->query($this->credentials);

        $this->assertArrayHasKey(
            'username',
            $resultArray,
            "I expect user credentials has 'username' key."
        );
        $this->assertArrayHasKey(
            'password',
            $resultArray,
            "I expect user credentials has 'password' key."
        );
        $this->assertArrayHasKey(
            'remember_token',
            $resultArray,
            "I expect user credentials has 'remember_token' key."
        );

        if ($this->assertArrayHasKey(
            'email',
            $resultArray,
            "I expect user credentials has 'email' key."
        )
        ) {
            $this->assertEquals($resultArray['email'], 'user@example.com', 'I expect email is equal to "user@example.com"');
        }
    }

    public function testRecall()
    {
        $token = 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw';

        $this->dbTable->updateRememberToken($token, 'user@example.com');
        $resultArray = $this->dbTable->recall($token);

        $this->assertArrayHasKey(
            'username',
            $resultArray,
            "I expect user credentials has 'username' key."
        );
        $this->assertArrayHasKey(
            'password',
            $resultArray,
            "I expect user credentials has 'password' key."
        );

        if ($this->assertArrayHasKey(
            'email',
            $resultArray,
            "I expect user credentials has 'email' key."
        )
        ) {
            $this->assertEquals($resultArray['email'], 'user@example.com', 'I expect email is equal to "user@example.com"');
        }
    }

    public function testUpdateRememberToken()
    {
        $token = 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw';
        $this->dbTable->updateRememberToken($token, 'user@example.com');
        
        $db = $this->container->get('database:default');
        $stmt = $db->query("SELECT * FROM users WHERE remember_token = ".$db->quote($token));
        $row = $stmt->fetch();

        $this->assertEquals($row['remember_token'], $token, "I expect that the value is equal to $token");
    }

}
