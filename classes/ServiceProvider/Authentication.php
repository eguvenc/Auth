<?php

namespace ServiceProvider;

use League\Container\Argument\RawArgument;
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
        'Auth:Provider',
        'Auth:Password',
        'Auth:Storage',
        'Auth:Identity',
        'Auth:RecallerToken',
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
        $request   = $container->get('request');
        $server    = $request->getServerParams();

        // Config
        //
        $container->share(
            'Auth.RECALLER_COOKIE',
            [
                'name' => '__rm',
                'domain' => '',
                'path' => '/',
                'secure' => false,
                'httpOnly' => false,
                'expire' => 6 * 30 * 24 * 3600,
            ]
        );
        $container->share('Auth.PASSWORD_COST', 6);
        $container->share('Auth.PASSWORD_ALGORITHM', PASSWORD_BCRYPT);
        $container->share('Auth.REMOTE_ADDR', new RawArgument($server['REMOTE_ADDR']));
        $container->share('Auth.HTTP_USER_AGENT', new RawArgument($server['HTTP_USER_AGENT']));

        // Services
        //
        $container->share('Auth:Password', 'Obullo\Auth\Password')
            ->withArgument($container);

        $container->share('Auth:Storage', 'Obullo\Auth\Storage\Redis')
            ->withArgument($container->get('redis:default'))
            ->withMethodCall('setContainer', [$container])
            ->withMethodCall('setPermanentBlockLifetime', [3600]) // Should be same with app session lifetime.
            ->withMethodCall('setTemporaryBlockLifetime', [300]);

        $container->share('Auth:Provider', 'Obullo\Auth\Provider\Doctrine')
            ->withArgument($container->get('doctrine:default'))
            ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
            ->withMethodCall('setTableName', ['users'])
            ->withMethodCall('setIdentityColumn', ['email'])
            ->withMethodCall('setPasswordColumn', ['password'])
            ->withMethodCall('setRememberTokenColumn', ['remember_token']);

        $container->share('Auth:RecallerToken', 'Obullo\Auth\RecallerToken')
            ->withArgument($container);

        $container->share('Auth:Identity', 'Obullo\Auth\Identity\Identity')
            ->withArgument($container)
            ->withMethodCall('setRequest', [$request]);
    }
}
