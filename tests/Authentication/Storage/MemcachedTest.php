<?php

use Obullo\Authentication\Storage\Memcached as MemcachedStorage;

class MemcachedTest extends WebTestCase
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

        $this->storage = new MemcachedStorage(
            $this->container->get('memcached:default'),
            $this->request
        );
        $this->credentials = [
            $this->table->getIdentityColumn() => 'user@example.com',
            $this->table->getPasswordColumn() => '12346',
        ];
        $this->storage->setIdentifier('user@example.com');
    }

}