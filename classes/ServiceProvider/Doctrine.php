<?php

namespace ServiceProvider;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

use League\Container\ServiceProvider\AbstractServiceProvider;

class Doctrine extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'doctrine:default'
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     *
     * @return void
     */
    public function register()
    {
        $container = $this->getContainer();

        $config = new Configuration;
        $connectionParams = array(
            'dbname' => 'test',
            'user' => 'root',
            'password' => '123456',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        );
        $conn = DriverManager::getConnection($connectionParams, $config);

        $container->share('doctrine:default', $conn);
    }
}
