<?php

use Obullo\Auth\AuthResult;

class AuthResultTest extends WebTestCase
{
    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    public function testIsValid()
    {
        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com',
            ['Wrong credentials.']
        );
        $isValid = $authResult->isValid();
        $this->assertFalse($isValid, "I expect that the result is invalid");

        $authResult = new AuthResult(
            AuthResult::SUCCESS,
            'user@example.com',
            ['Login successfull.']
        );
        $isValid = $authResult->isValid();
        $this->assertTrue($isValid, "I expect that the result is valid");
    }

    public function testGetCode()
    {
        $authResult = new AuthResult(
            AuthResult::SUCCESS,
            'user@example.com'
        );
        $this->assertEquals($authResult->getCode(), AuthResult::SUCCESS, "I expect that the result is equal to ".AuthResult::SUCCESS);

        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com'
        );
        $this->assertEquals($authResult->getCode(), AuthResult::FAILURE, "I expect that the result is equal to ".AuthResult::FAILURE);

        $authResult = new AuthResult(
            AuthResult::FAILURE_IDENTITY_AMBIGUOUS,
            'user@example.com'
        );
        $this->assertEquals($authResult->getCode(), AuthResult::FAILURE_IDENTITY_AMBIGUOUS, "I expect that the result is equal to ".AuthResult::FAILURE_IDENTITY_AMBIGUOUS);

        $authResult = new AuthResult(
            AuthResult::FAILURE_CREDENTIAL_INVALID,
            'user@example.com'
        );
        $this->assertEquals($authResult->getCode(), AuthResult::FAILURE_CREDENTIAL_INVALID, "I expect that the result is equal to ".AuthResult::FAILURE_CREDENTIAL_INVALID);
    }

    public function testGetIdentifier()
    {
        $authResult = new AuthResult(
            AuthResult::SUCCESS,
            'user@example.com'
        );
        $this->assertEquals($authResult->getIdentifier(), 'user@example.com', 'I expect that the result is equal to user@example.com');
    }

    public function testGetMessages()
    {
        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com',
            [
                'Wrong credentials',
                'Login failure'
            ]
        );
        $messages = $authResult->getMessages();

        if ($this->assertArrayHasKey(0, $messages)) {
            $this->assertEquals($messages[0], 'Wrong credentials');
        }
        if ($this->assertArrayHasKey(1, $messages)) {
            $this->assertEquals($messages[1], 'Login failure');
        }
    }

    public function testSetCode()
    {
        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com',
            [
                'Wrong credentials',
                'Login failure'
            ]
        );
        $authResult->setCode(AuthResult::FAILURE_IDENTITY_AMBIGUOUS);
        $this->assertEquals(AuthResult::FAILURE_IDENTITY_AMBIGUOUS, $authResult->getCode(), 'I expect that the code is equal to '.AuthResult::FAILURE_IDENTITY_AMBIGUOUS);
    }

    public function testSetMessage()
    {
        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com',
            [
                'Wrong credentials',
            ]
        );
        $authResult->setMessage('Login failure');
        $messages = $authResult->getMessages();

        if ($this->assertArrayHasKey(0, $messages)) {
            $this->assertEquals($messages[0], 'Wrong credentials');
        }
        if ($this->assertArrayHasKey(1, $messages)) {
            $this->assertEquals($messages[1], 'Login failure');
        }
    }

    public function testGetArray()
    {
        $authResult = new AuthResult(
            AuthResult::FAILURE,
            'user@example.com',
            ['Wrong credentials.']
        );
        $result = $authResult->getArray();

        if ($this->assertArrayHasKey('code', $result)) {
            $this->assertEquals($result['code'], AuthResult::FAILURE);
        }
        if ($this->assertArrayHasKey('identifier', $result)) {
            $this->assertEquals($result['identifier'], AuthResult::FAILURE);
        }
        if ($this->assertArrayHasKey('messages', $result)) {
            $this->assertEquals($result['messages'][0], 'Wrong credentials');
        }
    }

    public function testSetResultRow()
    {
        $authResult = new AuthResult(
            AuthResult::SUCCESS,
            'user@example.com',
            ['Login successfull.']
        );
        $resultArray = [
            'username' => 'test',
            'email' => 'user@example.com',
            'password' => '$2y$06$QRU3zQG0YnpDO8UW6ULATeTu0Z0wVF8fkozxoebPg8zu1LXXAwwf2',
            'remember_token' => 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw',
        ];
        $authResult->setResultRow($resultArray);
        $resultRow = $authResult->getResultRow();

        if ($this->assertArrayHasKey('username', $resultRow)) {
            $this->assertEquals($resultRow['username'], 'test');
        }
        if ($this->assertArrayHasKey('email', $resultRow)) {
            $this->assertEquals($resultRow['email'], 'user@example.com');
        }
        if ($this->assertArrayHasKey('password', $resultRow)) {
            $this->assertEquals(
                $resultRow['password'],
                '$2y$06$QRU3zQG0YnpDO8UW6ULATeTu0Z0wVF8fkozxoebPg8zu1LXXAwwf2'
            );
        }
        if ($this->assertArrayHasKey('remember_token', $resultRow)) {
            $this->assertEquals($resultRow['remember_token'], 'ztxq7SVTlk03Km25wqcYQDrk104UXvRw');
        }
    }
}
