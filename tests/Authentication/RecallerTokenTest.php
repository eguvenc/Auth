<?php

use Obullo\Auth\WebTestCase;

class RecallerTokenTest extends WebTestCase
{
    protected $recallerToken;

    /**
     * Setup variables
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->recallerToken = $this->container->get('Auth:RecallerToken');
    }

    public function testCreate()
    {
        $token = $this->recallerToken->create();

        $this->assertTrue(ctype_alnum($token), "I expect that the token value is alfanumeric.");
        $this->assertEquals(strlen($token), 32, "I expect length of token value that is equal to 32.");
    }

    public function testRemove()
    {
        $result = $this->recallerToken->remove();

        $this->assertEmpty($result, "Disable cookie remove method.");
    }

    public function testGenerate()
    {
        $token = $this->recallerToken->generate();

        $this->assertTrue(ctype_alnum($token), "I expect that the token value is alfanumeric.");
        $this->assertEquals(strlen($token), 32, "I expect length of token value that is equal to 32.");
    }
}
