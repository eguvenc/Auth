<?php

use Obullo\Auth\Password;
use Obullo\Auth\WebTestCase;
use Obullo\Auth\AuthAdapter;
use Obullo\Auth\RecallerToken;
use Obullo\Auth\Credentials;

class IdentityTest extends WebTestCase
{
    protected $db;
    protected $identity;
    protected $provider;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->identity = $this->container->get("Auth:Identity");
        $this->provider = $this->container->get("Auth:Provider");
    }

    /**
     * Login function
     *
     * @param integer $rememberMe int
     *
     * @return boolean
     */
    protected function login($rememberMe = 0)
    {
        $authAdapter = new AuthAdapter($this->container);

        $credentials = new Credentials;
        $credentials->setIdentityValue("user@example.com");
        $credentials->setPasswordValue("123456");
        $credentials->setRememberMeValue($rememberMe);

        $authResult = $authAdapter->authenticate($credentials);

        if (! $authResult->isValid()) {
            $messages = array();
            foreach ($authResult->getMessages() as $msg) {
                $messages[] = $msg;
            };
            trigger_error(implode("\n", $messages));
            return false;
        }
        $user = new Obullo\Auth\User($credentials);
        $user->setResultRow($authResult->getResultRow());

        $identity = $authAdapter->authorize($user); // Authorize user
        $identity->initialize();
        return true;
    }

    public function testGetIdentifier()
    {
        $this->login();
        $this->assertEquals(
            $this->identity->getIdentifier(),
            'user@example.com',
            "I expect identifier value that is equal to user@example.com."
        );
        $this->identity->destroy();
    }

    public function testGetPassword()
    {
        $this->login();
        $this->assertNotEmpty($this->identity->getPassword(), "I expect password value that is not empty.");
        $this->identity->destroy();
    }

    public function testGetRememberMe()
    {
        $this->login();
        $this->identity->set('__rememberMe', 1);
        $this->assertInternalType('integer', $this->identity->getRememberMe(), "I expect __rememberMe value that is an integer.");
        $this->assertEquals($this->identity->getRememberMe(), 1, "I expect __rememberMe value that is 1.");
        $this->identity->destroy();
    }

    public function testGet()
    {
        $this->login();
        $isAuthenticated = $this->identity->get('__isAuthenticated');
        $this->assertNotEmpty($isAuthenticated, "I expect get value that is not empty.");
        $this->identity->destroy();
    }

    public function testSet()
    {
        $this->login();
        $this->identity->set('test', 'hello');
        $this->assertEquals($this->identity->get('test'), 'hello', "I expect get value that is equal to hello.");
        $this->identity->destroy();
    }

    public function testGetArray()
    {
        $this->login();
        $array = $this->identity->getArray();
        $this->assertArrayHasKey('__isAuthenticated', $array, "I expect identity array has '__isAuthenticated' key.");
        $this->identity->destroy();
    }

    public function remove()
    {
        $this->identity->set('test', 123);
        $this->identity->remove('test');
        $this->assertFalse($this->identity->get('test'), "I expect that the value equal to false.");
    }

    public function testHasRecallerCookie()
    {
        $request = $this->container->get('request');
        $request = $request->withCookieParams(['__rm' => 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17']);
        $this->identity->setRequest($request);

        $this->assertEquals(
            'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17',
            $this->identity->hasRecallerCookie('fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17'),
            "I expect that the value is fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17."
        );
    }

    public function testGuest()
    {
        $this->identity->destroy();
        $this->assertTrue($this->identity->guest(), "I logout, then i expect that the value is true.");
    }

    public function testCheck()
    {
        $this->login();
        $this->assertTrue($this->identity->check(), "I login then i expect that the value is true.");
        $this->identity->destroy();
    }

    public function testValidateRecaller()
    {
        $token = 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17';
        $this->assertEquals($this->identity->validateRecaller($token), $token, "I set a recaller token then i expect that the value is 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17'.");

        $this->assertFalse(false, $this->identity->validateRecaller('1235454sd'), "I set a wrong recaller token then i expect that the value is false.");
    }

    public function testIsTemporary()
    {
        if ($this->identity->isTemporary() == false && $this->identity->guest()) {
            $this->login();
            $this->identity->makeTemporary();
        }
        $this->assertTrue($this->identity->isTemporary(), "I login then i set a temporary identity and i expect that the value is true.");
        $this->identity->destroyTemporary();
        $this->identity->destroy();
    }

    public function testExpire()
    {
        $this->login();
        $time  = time();
        $this->identity->expire(1);
        $sleep = $time - 2;

        $this->assertGreaterThan($sleep, $this->identity->get('__expire'), "I login.Then i set identity as expired and i expect to $sleep is greater than __expire value.");

        $this->identity->destroy();
    }

    public function testMakeTemporary()
    {
        $this->login();
        $this->identity->makeTemporary();
        $this->assertTrue($this->identity->isTemporary(), "I login as temporary.I expect that the value is true.");

        if ($this->identity->isTemporary()) {
            $this->identity->destroyTemporary();  // Destroy temporary identity
        }
        $this->identity->destroy();
    }

    public function testMakePermanent()
    {
        $this->login();
        $this->identity->makeTemporary();  // Make temporary user.
        $this->identity->makePermanent();  // Make permanent user.

        $this->assertFalse($this->identity->isTemporary(), "I login as temporary.Then i set it identity as permanent and i expect that the value is false.");
        $this->identity->destroy();
    }

    public function testGetTime()
    {
        $this->login();
        $time = $this->identity->getTime();
        if ($this->assertInternalType('integer', $time, "I expect that the value of time is integer.")) {
            $this->assertDate($time, "I expect that the date is valid.");
        }
        $this->identity->destroy();
    }

    public function testGetRememberToken()
    {
        $this->login();
        $recallerToken = new RecallerToken($this->container);

        $token = $recallerToken->generate();
        $this->identity->set('__rememberToken', $token);
        $token = $this->identity->getRememberToken();

        $this->assertEquals(32, strlen($token), "I expect length of value that is equal to 32.");
        $this->identity->destroy();
    }

    public function testLogout()
    {
        $this->login();
        $this->identity->logout();
        $credentials = $this->identity->getArray();

        $this->assertArrayHasKey('__isAuthenticated', $credentials, "I expect user credentials has '__isAuthenticated' key.");
        $this->assertEquals($credentials['__isAuthenticated'], 0, "I expect value of '__isAuthenticated' that is equal to 0.");
        $this->identity->destroy();
    }

    public function testDestroy()
    {
        $this->login();
        $this->identity->destroy();
        $this->assertFalse($this->identity->exists(), "I destroy the identiy and i expect that the value is false.");
    }

    public function testUpdateTemporary()
    {
        $this->login();
        $this->identity->makeTemporary();
        $this->identity->updateTemporary('test', 'test-value');

        $this->assertEquals($this->identity->get('test'), "test-value", "I create temporay identiy then i update it with 'test-value' and i expect that the value is equal to it.");
        $this->identity->destroyTemporary();
        $this->identity->destroy();
    }

    public function testDestroyTemporary()
    {
        $this->login();
        $this->identity->makeTemporary();
        $this->identity->destroyTemporary();
        $this->assertFalse($this->identity->exists(), "I destroy the identiy and i expect that the value is false.");
        $this->identity->destroy();
    }

    public function testUpdateRememberToken()
    {
        $sql = 'SELECT remember_token FROM users WHERE id = 1';
        
        $this->login();
        $this->db = $this->container->get('database:default');

        $this->identity->set('__rememberMe', 1);
        $beforeRow = $this->db->query($sql)->fetch();
        $this->identity->updateRememberToken();
        $this->identity->set('__rememberMe', 0);

        $afterRow = $this->db->query($sql)->fetch();

        $this->assertNotEquals($beforeRow['remember_token'], $afterRow['remember_token'], "I check remember_token from database and i expect that the value is not equal to old value.");
        
        $alnum = ctype_alnum($afterRow['remember_token']);

        $this->assertTrue($alnum, "I expect that the value is alfanumeric.");
        $this->assertEquals(strlen($afterRow['remember_token']), 32, "I expect length of value that is equal to 32.");
        $this->identity->destroy();
    }

    public function testGetLoginId()
    {
        $this->login();
        $loginId = $this->identity->getLoginId();  // 87010e88
        $this->assertEquals(strlen($loginId), 32, "I expect that the length of string is 32.");
        $this->identity->destroy();
    }
}
