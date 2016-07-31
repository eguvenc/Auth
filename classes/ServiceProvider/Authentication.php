<?php

namespace ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;

class Authentication extends AbstractServiceProvider
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
        'auth:table',
        'auth:adapter',
        'auth:storage',
        'auth:identity',
        'auth:rememberMe',
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

        // Auth Config
        //
        $container->share('auth.passwordCost', 6);
        $container->share('auth.passwordAlgorithm', PASSWORD_BCRYPT);

        // Auth Services
        //
        $container->share('auth:storage', 'Obullo\Authentication\Storage\Redis')
            ->withArgument($container->get('redis:default'))
            ->withArgument($container->get('request'))
            ->withMethodCall('setPermanentBlockLifetime', [3600])  // Should be same with app session lifetime.
            ->withMethodCall('setTemporaryBlockLifetime', [300]);

        $container->share('auth:table', 'Obullo\Authentication\Adapter\Database\Table\Db')
            ->withArgument($container->get('database:default'))
            ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
            ->withMethodCall('setTableName', ['users'])
            ->withMethodCall('setIdentityColumn', ['email'])
            ->withMethodCall('setPasswordColumn', ['password'])
            ->withMethodCall('setRememberTokenColumn', ['remember_token']);

        $container->share('auth:rememberMe', 'Obullo\Authentication\RememberMe')
            ->withArgument($container->get('request'))
            ->withArgument(
                [
                    'name' => '__rm',
                    'domain' => null,
                    'path' => '/',
                    'secure' => false,
                    'httpOnly' => true,
                    'expire' => 6 * 30 * 24 * 3600,
                ]
            );
        $container->share('auth:adapter', 'Obullo\Authentication\Adapter\Database\Database')
            ->withArgument($container);

        $container->share('auth:identity', 'My\Identity')
            ->withArgument($container);
    }
}
