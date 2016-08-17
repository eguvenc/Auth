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

        // Servie Config
        //
        $container->share('Auth.PASSWORD_COST', 6);
        $container->share('Auth.PASSWORD_ALGORITHM', PASSWORD_BCRYPT);
        
        // Request Config
        //
        $server  = $container->get('request')->getServerParams();
        $request = $container->get('request')->withAttribute(
            'Auth_Request',
            [
                'REMOTE_ADDR'     => $server['REMOTE_ADDR'],
                'HTTP_USER_AGENT' => $server['HTTP_USER_AGENT']
            ]
        );
        // Services
        //
        $container->share('Auth:Storage', 'Obullo\Auth\MFA\Storage\Redis')
            ->withArgument($container->get('redis:default'))
            ->withArgument($request)
            ->withMethodCall('setPermanentBlockLifetime', [3600]) // Should be same with app session lifetime.
            ->withMethodCall('setTemporaryBlockLifetime', [300]);

        $container->share('Auth:Table', 'Obullo\Auth\MFA\Adapter\Database\Table\Db')
            ->withArgument($container->get('database:default'))
            ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
            ->withMethodCall('setTableName', ['users'])
            ->withMethodCall('setIdentityColumn', ['email'])
            ->withMethodCall('setPasswordColumn', ['password'])
            ->withMethodCall('setRememberTokenColumn', ['remember_token']);

        $container->share('Auth:RememberMe', 'Obullo\Auth\MFA\RememberMe')
            ->withArgument($request)
            ->withArgument(
                [
                    'name' => '__rm',
                    'domain' => '',
                    'path' => '/',
                    'secure' => false,
                    'httpOnly' => false,
                    'expire' => 6 * 30 * 24 * 3600,
                ]
            );
        $container->share('Auth:Identity', 'Obullo\Auth\MFA\Identity\Identity')
            ->withArgument($container);
    }
}
