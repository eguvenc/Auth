<?php

use Obullo\Auth\User\Credentials;
use Obullo\Auth\Adapter\Table\Mongo;

class MongoTest extends WebTestCase
{
    protected $credentials;
    protected $mongoTable;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->container->addServiceProvider('ServiceProvider\Mongo');

        $this->credentials = new Credentials;
        $this->credentials->setIdentityValue("user@example.com");
        $this->credentials->setPasswordValue("123456");

        $db = $this->container->get('mongo:default')->selectDB('test');

        $this->mongoTable = new Mongo($db);
        $this->mongoTable->setColumns(array('username', 'password', 'email', 'remember_token'));
        $this->mongoTable->setTableName('users');
        $this->mongoTable->setIdentityColumn('email');
        $this->mongoTable->setPasswordColumn('password');
        $this->mongoTable->setRememberTokenColumn('remember_token');
    }

    public function testQuery()
    {
        $resultArray = $this->mongoTable->query($this->credentials);

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

        $this->mongoTable->updateRememberToken($token, 'user@example.com');
        $resultArray = $this->mongoTable->recall($token);

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
        $this->mongoTable->updateRememberToken($token, 'user@example.com');
        
        $db = $this->container->get('database:default');
        $stmt = $db->query("SELECT * FROM users WHERE remember_token = ".$db->quote($token));
        $row = $stmt->fetch();

        $this->assertEquals($row['remember_token'], $token, "I expect that the value is equal to $token");
    }

}
