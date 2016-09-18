<?php

use Obullo\Auth\User\Credentials;
use Obullo\Auth\Adapter\Database\Database;

class IdentityTest extends WebTestCase
{
    protected $db;
    protected $identity;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->identity = $this->container->get("Auth:Identity");
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
        $authAdapter = new Database($this->container);
        $authAdapter->setRequest($this->container->get('request'));
        $authAdapter->regenerateSessionId(true);

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
            $this->fail(implode("\n", $messages));
            return false;
        }
        $this->identity->initialize();
        return true;
    }

    /**
     * Returns true if user has recaller cookie (__rm).
     *
     * @return false|string token
     */
    public function testHasRecallerCookie()
    {
        $request = $this->container->get('request');
        $request = $request->withCookieParams(['__rm' => 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17']);

        $rememberMe = new RememberMe(
            $request,
            [
                'name' => '__rm',
                'domain' => '',
                'path' => '/',
                'secure' => false,
                'httpOnly' => false,
                'expire' => 6 * 30 * 24 * 3600,
            ]
        );
        $this->assertEquals(
            'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17',
            $this->identity->hasRecallerCookie($rememberMe->readToken()),
            "I expect that the value is fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17."
        );
    }

    /**
     * Guest
     *
     * @return void
     */
    public function testGuest()
    {
        $this->identity->destroy();
        $this->assertTrue($this->identity->guest(), "I logout, then i expect that the value is true.");
    }

    /**
     * Check
     *
     * @return void
     */
    public function testCheck()
    {
        $this->login();
        $this->assertTrue($this->identity->check(), "I login then i expect that the value is true.");
        $this->identity->destroy();
    }

    /**
     * Check recaller cookie
     *
     * @return void
     */
    public function testValidateRecaller()
    {
        $token = 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17';

        $this->assertEquals($this->identity->validateRecaller($token), $token, "I set a recaller token then i expect that the value is 'fgvH6hrlWNDeb9jz5L2P4xBW3vdrDP17'.");

        $this->assertFalse(false, $this->identity->validateRecaller('1235454sd'), "I set a wrong recaller token then i expect that the value is false.");
    }

    /**
     * Check auth is temporary
     *
     * @return void
     */
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

    /**
     * Expire permanent identity
     *
     * @return void
     */
    public function testExpire()
    {
        $this->login();

        $time  = time();
        $this->identity->expire(1);
        $sleep = $time - 2;

        $this->assertGreaterThan($sleep, $this->identity->get('__expire'), "I login.Then i set identity as expired and i expect to $sleep is greater than __expire value.");

        $this->identity->destroy();
    }

    /**
     * Make temporary user
     *
     * @return void
     */
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

    /**
     * Make permanent user
     *
     * @return void
     */
    public function testMakePermanent()
    {
        $this->login();

        $this->identity->makeTemporary();  // Make temporary user.
        $this->identity->makePermanent();  // Make permanent user.

        $this->assertFalse($this->identity->isTemporary(), "I login as temporary.Then i set it identity as permanent and i expect that the value is false.");
        $this->identity->destroy();
    }

    /**
     * Returns to time
     *
     * @return void
     */
    public function testGetTime()
    {
        $this->login();

        $time = $this->identity->getTime();
        if ($this->assertInternalType('integer', $time, "I expect that the value of time is integer.")) {
            $this->assertDate($time, "I expect that the date is valid.");
        }
        $this->identity->destroy();
    }

    /**
     * Returns to time
     *
     * @return void
     */
    public function testGetArray()
    {
        $this->login();

        $array = $this->identity->getArray();
        $this->assertArrayHasKey('__isAuthenticated', $array, "I expect identity array has '__isAuthenticated' key.");
        $this->identity->destroy();
    }

    /**
     * Returns to "1" user if used remember me
     *
     * @return void
     */
    public function testGetRememberMe()
    {
        $this->login();

        $this->identity->set('__rememberMe', 1);
        $this->assertInternalType('integer', $this->identity->getRememberMe(), "I expect __rememberMe value that is an integer.");
        $this->assertEquals($this->identity->getRememberMe(), 1, "I expect __rememberMe value that is 1.");
        $this->identity->destroy();
    }

    /**
     * Returns to remember token
     *
     * @return void
     */
    public function testGetRememberToken()
    {
        $this->login();

        $request    = $this->container->get('request');
        $rememberMe = new RememberMe(
            $request,
            [
                'name' => '__rm',
                'domain' => '',
                'path' => '/',
                'secure' => false,
                'httpOnly' => false,
                'expire' => 6 * 30 * 24 * 3600,
            ]
        );
        $token = $rememberMe->generateToken();
        $this->identity->set('__rememberToken', $token);
        $token = $this->identity->getRememberToken();

        $this->assertEquals(32, strlen($token), "I expect length of value that is equal to 32.");
        $this->identity->destroy();
    }

    /**
     * Sets authority of user to "0" don't touch to cached data
     *
     * @return void
     */
    public function testLogout()
    {
        $this->login();

        $this->identity->logout();
        $credentials = $this->identity->getArray();

        $this->assertArrayHasKey('__isAuthenticated', $credentials, "I expect user credentials has '__isAuthenticated' key.");
        $this->assertEquals($credentials['__isAuthenticated'], 0, "I expect value of '__isAuthenticated' that is equal to 0.");
        $this->identity->destroy();
    }

    /**
     * Destroy permanent identity of authorized user
     *
     * @return void
     */
    public function testDestroy()
    {
        $this->login();
        
        $this->identity->destroy();
        $this->assertFalse($this->identity->exists(), "I destroy the identiy and i expect that the value is false.");
    }

    /**
     * Update temporary credentials
     *
     * @return void
     */
    public function testUpdateTemporary()
    {
        $this->login();

        $this->identity->makeTemporary();
        $this->identity->updateTemporary('test', 'test-value');

        $this->assertEquals($this->identity->get('test'), "test-value", "I create temporay identiy then i update it with 'test-value' and i expect that the value is equal to it.");
        $this->identity->destroyTemporary();
        $this->identity->destroy();
    }

    /**
     * Destroy temporary identity of unauthorized user
     *
     * @return void
     */
    public function testDestroyTemporary()
    {
        $this->login();

        $this->identity->makeTemporary();
        $this->identity->destroyTemporary();
        $this->assertFalse($this->identity->exists(), "I destroy the identiy and i expect that the value is false.");
        $this->identity->destroy();
    }

    /**
     * Update remember token if it exists in the memory and browser header
     *
     * @return void
     */
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

    /**
     * Validate credentials authorized user credentials
     *
     * @return void
     */
    public function testValidate()
    {
        $this->login();

        $i = $this->container->get('user.params')['db.identifier'];
        $p = $this->container->get('user.params')['db.password'];

        $credentials = $this->config->get('tests')['login']['credentials'];

        $isValid = $this->identity->validate([$i => $credentials['username'], $p => $credentials['password']]);
        $this->assertTrue($isValid, "I login.Then i validate user credentials and i expect that the value is true.");
        $this->identity->destroy();
    }

    /**
     * Returns to login id of user, its an unique id for each browsers.
     *
     * @return void
     */
    public function getLoginId()
    {
        $this->login();

        $loginId = $this->identity->getLoginId();  // 87010e88
        $this->assertInternalType('alnum', $loginId, "I expect that the value is alfanumeric.");
        $this->assertEquals(strlen($loginId), 32, "I expect that the length of string is 32.");
        $this->identity->destroy();
    }

    /**
     * Kill authority of user using auth id
     *
     * @return void
     */
    public function kill()
    {
        $this->login();

        $loginId = $this->identity->getLoginId();
        $this->identity->kill($loginId);
        $this->assertEmpty($this->user->storage->getCredentials(), "I login.Then i kill identity with my login id and i expect that the identity data is empty.");
        
        $this->identity->destroy();
    }
}
