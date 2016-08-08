<?php

use Obullo\Authentication\Credentials;

class IdentityTest extends WebTestCase
{
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
        $authAdapter = $this->container->get('Auth:Adapter');
        $authAdapter->regenerateSessionId(true);

        $credentials = new Credentials;
        $credentials->setIdentityValue("user@example.com");
        $credentials->setPasswordValue("123456");
        $credentials->setRememberMeValue($rememberMe);

        $authResult = $authAdapter->login($credentials);

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

        $this->assertEquals($this->identity->validateRecaller($token), $token, "I set a recaller token then i expect that the value is true.");
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
    // public function testExpire()
    // {
    //     $this->login();

    //     $time = time();
    //     $this->identity->expire(1);

    //     $this->assertGreaterThan($this->identity->get('__expire'), $time, "I login.Then i set identity as expired and i expect to $time is greater than __expire value.");

    //     $this->identity->destroy();
    // }
}
