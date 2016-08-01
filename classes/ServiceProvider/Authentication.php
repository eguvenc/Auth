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
        'Auth:Table',
        'Auth:Adapter',
        'Auth:Storage',
        'Auth:Identity',
        'Auth:RememberMe',
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
        $container->share('Auth.PASSWORD_COST', 6);
        $container->share('Auth.PASSWORD_ALGORITHM', PASSWORD_BCRYPT);

        // Auth Services
        //
        $container->share('Auth:Storage', 'Obullo\Authentication\Storage\Redis')
            ->withArgument($container->get('Redis:Default'))
            ->withArgument($container->get('Request'))
            ->withMethodCall('setPermanentBlockLifetime', [3600]) // Should be same with app session lifetime.
            ->withMethodCall('setTemporaryBlockLifetime', [300]);

        $container->share('Auth:Table', 'Obullo\Authentication\Adapter\Database\Table\Db')
            ->withArgument($container->get('Database:Default'))
            ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
            ->withMethodCall('setTableName', ['users'])
            ->withMethodCall('setIdentityColumn', ['email'])
            ->withMethodCall('setPasswordColumn', ['password'])
            ->withMethodCall('setRememberTokenColumn', ['remember_token']);

        $container->share('Auth:RememberMe', 'Obullo\Authentication\RememberMe')
            ->withArgument($container->get('Request'))
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
        $container->share('Auth:Adapter', 'Obullo\Authentication\Adapter\Database\Database')
            ->withArgument($container);

        $container->share('Auth:Identity', 'My\Identity')
            ->withArgument($container);
    }
}
