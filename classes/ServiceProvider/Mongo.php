<?php

namespace ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;

class Mongo extends AbstractServiceProvider
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
        'mongo:default'
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

        $mongoClass = (version_compare(phpversion('mongo'), '1.3.0', '<')) ? '\Mongo' : '\MongoClient';

        if (! class_exists($mongoClass, false)) {
            throw new \RuntimeException(
                sprintf(
                    'The %s extension has not been installed or enabled.',
                    trim($mongoClass, '\\')
                )
            );
        }
        $mongo = new $mongoClass('mongodb://root:123456@localhost:27017', array('connect' => true));
        $container->share('mongo:default', $mongo);
    }
}
