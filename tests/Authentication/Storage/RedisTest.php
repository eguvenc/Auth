<?php

use Obullo\Auth\MFA\Storage\Redis as RedisStorage;

class RedisTest extends WebTestCase
{
    protected $table;
    protected $request;
    protected $storage;
    protected $credentials;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->table   = $this->container->get('Auth:Table');
        $this->request = $this->container->get('request');

        $this->storage = new RedisStorage(
            $this->container->get('redis:default'),
            $this->request
        );
        $this->credentials = [
            $this->table->getIdentityColumn() => 'user@example.com',
            $this->table->getPasswordColumn() => '12346',
        ];
        $this->storage->setIdentifier('user@example.com');
    }
    
    /**
     * Sets identifier value to session
     *
     * @return void
     */
    public function testSetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $test = 'test@example.com:'.$this->storage->getLoginId();
        $this->assertEquals($this->storage->getIdentifier(), $test, "I set a fake identifier then i expect that the value is '$test'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Returns to user identifier
     *
     * @return mixed string|id
     */
    public function testGetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $test = 'test@example.com:'.$this->storage->getLoginId();
        $this->assertEquals($this->storage->getIdentifier(), $test, "I set a fake identifier then i expect that the value is '$test'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Unset identifier from session
     *
     * @return void
     */
    public function testUnsetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $this->storage->unsetIdentifier('test@example.com');
        $this->assertEquals($this->storage->getIdentifier(), null, "I set a fake identifier then i remove it and i expect that the value is null.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Unset identifier from session
     *
     * @return void
     */
    public function testHasIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $this->assertTrue($this->storage->hasIdentifier(), "I set a fake identifier and i expect that the value is true.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();

        $this->assertFalse($this->storage->hasIdentifier(), "I remove the fake identifier and i expect that the value is false.");
    }

    /**
     * Register credentials as temporary
     *
     * @return void
     */
    public function testCreateTemporary()
    {
        $this->storage->createTemporary($this->credentials);
        $result = $this->storage->getCredentials('__temporary');

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create temporary credentials and i expect array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 0, "I expect that the value is 0.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 1, "I expect that the value is 1.");
        }
        $this->storage->deleteCredentials('__temporary');
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Register credentials to permanent storage
     *
     * @return void
     */
    public function testCreatePermanent()
    {
        $this->storage->createPermanent($this->credentials);
        $result = $this->storage->getCredentials();

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create permanent credentials and i expect array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 1, "I expect that the value is 1.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 0, "I expect that the value is 0.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Makes temporary credentials as permanent and authenticate the user.
     *
     * @return void
     */
    public function testMakePermanent()
    {
        $this->storage->createTemporary($this->credentials);
        $this->storage->makePermanent();
        $result = $this->storage->getCredentials();

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create temporary credentials then make them as permanent and i expect array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 1, "I expect that the value is 1.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 0, "I expect that the value is 0.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Makes permanent credentials as temporary and unauthenticate the user.
     *
     * @return void
     */
    public function testMakeTemporary()
    {
        $this->storage->createPermanent($this->credentials);
        $this->storage->makeTemporary();
        $result = $this->storage->getCredentials('__temporary');

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create temporary credentials then make them as permanent and i expect array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 0, "I expect that the value is 0.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 1, "I expect that the value is 1.");
        }
        $this->storage->deleteCredentials('__temporary');
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Get id of identifier without random Id value
     *
     * @return void
     */
    public function testGetUserId()
    {
        $this->assertEquals($this->storage->getUserId(), "user@example.com", "I expect that the value is user@example.com.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Get random id
     *
     * @return void
     */
    public function testGetLoginId()
    {
        unset($_SESSION[$this->storage->getCacheKey().'_LoginId']);

        $client    = $this->request->getAttribute('Auth_Client');
        $userAgent = substr($client['HTTP_USER_AGENT'], 0, 50); // First 50 characters of the user agent
        $loginId   = md5(trim($userAgent).time());
        $expected  = $this->storage->getLoginId();

        $this->assertEquals($loginId, $expected, "I expect that the value is $loginId.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Create login id
     *
     * @return string
     */
    public function testSetLoginId()
    {
        $id = strlen($this->storage->getLoginId());
        $this->assertEquals($id, 32, "I expect that the character length is 32.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Gey cache key
     *
     * @return string
     */
    public function testGetCacheKey()
    {
        $this->assertNotEmpty($this->storage->getCacheKey(), "I expect the storage key is not empty.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Get valid memory segment key
     *
     * @return void
     */
    public function testGetMemoryBlockKey()
    {
        $block = $this->storage->getCacheKey(). ':' .$this->storage->getIdentifier();
        $this->assertEquals($block, $this->storage->getMemoryBlockKey(), "I expect the block key equals to key '$block'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Returns to storage prefix key of identity data
     *
     * @return string
     */
    public function testGetUserKey()
    {
        $block = $this->storage->getCacheKey(). ':' .$this->storage->getUserId();
        $this->assertEquals($block, $this->storage->getUserKey(), "I expect the block key equals to key '$block'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Returns to memory block lifetime
     *
     * @return integer
     */
    public function testGetMemoryBlockLifetime()
    {
        $this->storage->setTemporaryBlockLifetime(400);
        $this->storage->setPermanentBlockLifetime(1500);

         $this->assertEquals(
             $this->storage->getPermanentBlockLifetime(),
             1500,
             "I expect the permanent block lifetime equals to service configuration lifetime value."
         );
        $this->assertEquals(
            $this->storage->getTemporaryBlockLifetime(),
            400,
            "I expect the temporary block lifetime equals to service configuration lifetime value."
        );
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Returns true if temporary credentials does "not" exists
     *
     * @return void
     */
    public function testIsEmpty()
    {
        $this->storage->createPermanent($this->credentials);

        $this->assertFalse($this->storage->isEmpty(), "I create identity and i expect that the value is false.");
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();

        $this->assertTrue($this->storage->isEmpty(), "I delete identity and i expect that the value is true.");
    }

    /**
     * Match the user credentials.
     *
     * @return void
     */
    public function testQuery()
    {
        $this->storage->createPermanent($this->credentials);
        $result = $this->storage->query();

        $identifier = $this->table->getIdentityColumn();
        $password   = $this->table->getPasswordColumn();

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create fake credentials i expect query array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 1, "I expect that the value is equal to 1.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect identity array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 0, "I expect that the value is equal to 0.");
        }
        if ($this->assertArrayHasKey($identifier, $result, "I expect identity array has '$identifier' key.")) {
            $this->assertEquals($result[$identifier], $credentials[$identifier], "I expect that the value is equal to ".$credentials[$identifier].".");
        }
        if ($this->assertArrayHasKey($password, $result, "I expect identity array has '$password' key.")) {
            $this->assertEquals($result[$password], $credentials[$password], "I expect that the value is equal to ".$credentials[$password].".");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Update credentials
     *
     * @return void
     */
    public function testSetCredentials()
    {
        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);
        $result = $this->storage->getCredentials();

        $identifier = $this->table->getIdentityColumn();
        $password   = $this->table->getPasswordColumn();

        if ($this->assertArrayHasKey('__isAuthenticated', $result, "I create fake credentials and i expect storage array has '__isAuthenticated' key.")) {
            $this->assertEquals($result['__isAuthenticated'], 1, "I expect that the value is equal to 1.");
        }
        if ($this->assertArrayHasKey('__isTemporary', $result, "I expect identity array has '__isTemporary' key.")) {
            $this->assertEquals($result['__isTemporary'], 0, "I expect that the value is equal to 0.");
        }
        if ($this->assertArrayHasKey($identifier, $result, "I expect identity array has '$identifier' key.")) {
            $this->assertEquals($result[$identifier], $credentials[$identifier], "I expect that the value is equal to ".$credentials[$identifier].".");
        }
        if ($this->assertArrayHasKey($password, $result, "I expect identity array has '$password' key.")) {
            $this->assertEquals($result[$password], $credentials[$password], "I expect that the value is equal to ".$credentials[$password].".");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Deletes memory block completely
     *
     * @return void
     */
    public function testDeleteCredentials()
    {
        $this->storage->setCredentials($this->credentials, array(), 60);
        $this->storage->deleteCredentials();
        $result = $this->storage->getCredentials();

        $this->assertEmpty($result, "I create fake credentials then i delete them and i expect that the value is true.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Update data
     *
     * @return void
     */
    public function testUpdate()
    {
        $identifier = $this->table->getIdentityColumn();

        $this->storage->setCredentials($this->credentials, array(), 60);
        $this->storage->update($identifier, 'test@example.com');
        $result = $this->storage->getCredentials();

        if ($this->assertArrayHasKey($identifier, $result, "I create fake credentials then i expect array has 'username' key.")) {
            $this->assertEquals('test@example.com', $result[$identifier], "I update username value as 'test@example.com' and i expect that the value is equal to 'test@example.com'.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Remove data
     *
     * @return void
     */
    public function testRemove()
    {
        $identifier = $this->table->getIdentityColumn();

        $this->storage->setCredentials($this->credentials, array(), 60);
        $this->storage->remove($identifier);

        $result = $this->storage->getCredentials();

        $this->assertArrayNotHasKey($identifier, $result, "I create fake credentials then i remove username key and i expect array has not '$identifier' key.");

        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Returns to all keys
     *
     * @return void
     */
    public function testGetAllKeys()
    {
        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);

        $data = $this->storage->getAllKeys();

        if ($this->assertArrayHasKey(0, $data, "I create fake credentials then i expect array has 0 key.")) {
            $this->assertNotEmpty($data[0], "I expect the key data is not empty.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Return to all sessions of current user
     *
     * @return array
     */
    public function testGetUserSessions()
    {
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);
        $this->credentials['__time'] = $microtime;

        $this->storage->createPermanent($this->credentials);
        $result  = $this->storage->getUserSessions();
        $loginId = $this->storage->getLoginId();

        if ($this->assertArrayHasKey($loginId, $result, "I create fake credentials then i expect array has '$loginId' key.")) {
            $cacheIdentifier = $result[$loginId]['key'];
            $this->assertEquals($cacheIdentifier, $this->storage->getMemoryBlockKey(), "I expect that the value of cache identifier is equal to $cacheIdentifier.");
            $this->assertArrayHasKey('__time', $result[$loginId], "I expect array has '__time' key.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    /**
     * Kill session using by login id
     *
     * @return void
     */
    public function testKillSession()
    {
        $this->storage->createPermanent($this->credentials);
        
        $sessions = $this->storage->getUserSessions();
        $loginId  = $this->storage->getLoginId();

        if ($this->assertArrayHasKey($loginId, $sessions, "I create fake credentials then i expect array has '$loginId' key.")) {
            $this->storage->killSession($loginId);
            $newSessions = $this->storage->getUserSessions();

            $this->assertArrayNotHasKey($loginId, $newSessions, "I expect the array has not key '$loginId'.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }
}
