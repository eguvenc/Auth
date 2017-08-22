<?php

use Obullo\Auth\WebTestCase;
use Obullo\Auth\Storage\Memcached as MemcachedStorage;

class MemcachedTest extends WebTestCase
{
    protected $provider;
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

        $this->provider = $this->container->get('Auth:Provider');
        $this->request  = $this->container->get('request');

        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);

        $this->storage = new MemcachedStorage(
            $this->container->get('memcached:default')
        );
        $this->storage->setContainer($this->container);
        
        $this->credentials = [
            $this->provider->getIdentityColumn() => 'user@example.com',
            $this->provider->getPasswordColumn() => '12346',
            '__time' => $microtime,
            '__ip' => "127.0.0.1",
            '__agent' => null,
            '__lastActivity' => time(),
        ];
        $this->storage->setIdentifier('user@example.com');
    }

    public function testSetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $test = 'test@example.com:'.$this->storage->getLoginId();
        $this->assertEquals($this->storage->getIdentifier(), $test, "I set a fake identifier then i expect that the value is '$test'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $test = 'test@example.com:'.$this->storage->getLoginId();
        $this->assertEquals($this->storage->getIdentifier(), $test, "I set a fake identifier then i expect that the value is '$test'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testUnsetIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $this->storage->unsetIdentifier('test@example.com');
        $this->assertEquals($this->storage->getIdentifier(), null, "I set a fake identifier then i remove it and i expect that the value is null.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testHasIdentifier()
    {
        $this->storage->setIdentifier('test@example.com');
        $this->assertTrue($this->storage->hasIdentifier(), "I set a fake identifier and i expect that the value is true.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();

        $this->assertFalse($this->storage->hasIdentifier(), "I remove the fake identifier and i expect that the value is false.");
    }

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

    public function testGetUserId()
    {
        $this->assertEquals($this->storage->getUserId(), "user@example.com", "I expect that the value is user@example.com.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetLoginId()
    {
        unset($_SESSION[$this->storage->getStoreKey().'_LoginId']);

        $client    = $this->request->getAttribute('Auth_Client');
        $userAgent = substr($client['HTTP_USER_AGENT'], 0, 50); // First 50 characters of the user agent

        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);
        $loginId = md5(trim($userAgent).$microtime);
        $_SESSION[$this->storage->getStoreKey().'_LoginId'] = $loginId;
        $expected  = $this->storage->getLoginId();

        $this->assertEquals($loginId, $expected, "I expect that the value is $loginId.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testSetLoginId()
    {
        $id = strlen($this->storage->getLoginId());
        $this->assertEquals($id, 32, "I expect that the character length is 32.");
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetCacheKey()
    {
        $this->assertNotEmpty($this->storage->getStoreKey(), "I expect the storage key is not empty.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetMemoryBlockKey()
    {
        $block = $this->storage->getStoreKey(). ':' .$this->storage->getIdentifier();
        $this->assertEquals($block, $this->storage->getMemoryBlockKey(), "I expect the block key equals to key '$block'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetUserKey()
    {
        $block = $this->storage->getStoreKey(). ':' .$this->storage->getUserId();
        $this->assertEquals($block, $this->storage->getUserKey(), "I expect the block key equals to key '$block'.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

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

    public function testIsEmpty()
    {
        $this->storage->createPermanent($this->credentials);

        $this->assertFalse($this->storage->isEmpty(), "I create identity and i expect that the value is false.");
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();

        $this->assertTrue($this->storage->isEmpty(), "I delete identity and i expect that the value is true.");
    }

    public function testQuery()
    {
        $this->storage->createPermanent($this->credentials);
        $result = $this->storage->query();

        $identifier = $this->provider->getIdentityColumn();
        $password   = $this->provider->getPasswordColumn();

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

    public function testSetCredentials()
    {
        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);
        $result = $this->storage->getCredentials();

        $identifier = $this->provider->getIdentityColumn();
        $password   = $this->provider->getPasswordColumn();

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

    public function testDeleteCredentials()
    {
        $this->storage->setCredentials($this->credentials, array(), 60);
        $this->storage->deleteCredentials();
        $result = $this->storage->getCredentials();

        $this->assertEmpty($result, "I create fake credentials then i delete them and i expect that the value is true.");

        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testUpdate()
    {
        $identifier = $this->provider->getIdentityColumn();

        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);
        $this->storage->update($identifier, 'test@example.com');
        $result = $this->storage->getCredentials();

        if ($this->assertArrayHasKey($identifier, $result, "I create fake credentials then i expect array has 'username' key.")) {
            $this->assertEquals('test@example.com', $result[$identifier], "I update username value as 'test@example.com' and i expect that the value is equal to 'test@example.com'.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testRemove()
    {
        $identifier = $this->provider->getIdentityColumn();

        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);
        $this->storage->remove($identifier);

        $result = $this->storage->getCredentials();

        $this->assertArrayNotHasKey($identifier, $result, "I create fake credentials then i remove username key and i expect array has not '$identifier' key.");

        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetActiveSessions()
    {
        $data = [
            '__isAuthenticated' => 1,
            '__isTemporary' => 0,
        ];
        $this->storage->setCredentials($this->credentials, $data, 60);

        $data = $this->storage->getActiveSessions();
        $key  = $this->storage->getLoginId();

        if ($this->assertArrayHasKey($key, $data, "I create fake credentials then i expect array has 0 key.")) {
            $this->assertNotEmpty($data[$key], "I expect the key data is not empty.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

    public function testGetUserSessions()
    {
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);
        $this->credentials['__time'] = $microtime;
        
        $this->storage->createPermanent($this->credentials);

        $sessions = $this->storage->getUserSessions();
        $loginId  = $this->storage->getLoginId();

        if ($this->assertArrayHasKey($loginId, $sessions, "I create fake credentials then i expect array has '$loginId' key.")) {
            $cacheIdentifier = $sessions[$loginId]['key'];
            $this->assertEquals($cacheIdentifier, $this->storage->getMemoryBlockKey(), "I expect that the value of cache identifier is equal to $cacheIdentifier.");
            $this->assertArrayHasKey('__time', $sessions[$loginId], "I expect array has '__time' key.");
        }
        $this->storage->deleteCredentials();
        $this->storage->unsetIdentifier();
        $this->storage->unsetLoginId();
    }

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

    public function testSetSessionIndex()
    {
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);

        $data = [
            '__lastActivity' => $microtime,
        ];
        $this->storage->setLoginId();
        $this->storage->setSessionIndex($data, 3600);
        $data = $this->storage->getSessionIndex();

        $loginID = $this->storage->getLoginId();
        $this->assertNotEmpty($loginID, "I set login id and i expect that is not empty.");

        if ($this->assertArrayHasKey($loginID, $data, "I set test index and i expect that array has $loginID key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        if ($this->assertArrayHasKey('lastActivity', $data[$loginID], "I set test index and i expect that array has lastActivity key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        $this->storage->deleteSessionIndex();
    }

    public function testGetSessionIndex()
    {
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);

        $data = [
            '__lastActivity' => $microtime,
        ];
        $this->storage->setLoginId();
        $this->storage->setSessionIndex($data, 3600);
        $data = $this->storage->getSessionIndex();

        $loginID = $this->storage->getLoginId();
        $this->assertNotEmpty($loginID, "I set login id and i expect that is not empty.");

        if ($this->assertArrayHasKey($loginID, $data, "I set test index and i expect that array has $loginID key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        if ($this->assertArrayHasKey('lastActivity', $data[$loginID], "I set test index and i expect that array has lastActivity key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        $this->storage->deleteSessionIndex();
    }

    public function testDeleteSessionIndex()
    {
        list($usec, $sec) = explode(" ", microtime());
        $microtime = ((float)$usec + (float)$sec);

        $data = [
            '__lastActivity' => $microtime,
        ];
        $this->storage->setLoginId();
        $this->storage->setSessionIndex($data, 3600);
        $data = $this->storage->getSessionIndex();

        $loginID = $this->storage->getLoginId();
        $this->assertNotEmpty($loginID, "I set login id and i expect that is not empty.");

        if ($this->assertArrayHasKey($loginID, $data, "I set test index and i expect that array has $loginID key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        if ($this->assertArrayHasKey('lastActivity', $data[$loginID], "I set test index and i expect that array has lastActivity key.")) {
            $this->asserType('float', $data['lastActivity']);
        }
        $this->storage->deleteSessionIndex();
    }

}
