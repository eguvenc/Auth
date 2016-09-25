<?php

use League\Container\Container;
use Zend\Diactoros\ServerRequestFactory;

/**
 * We create a Web test case for PHP Unit
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class WebTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Container
     *
     * @var object
     */
    protected $container;
    

    public function setUp()
    {
        $this->container = new Container;

        /**
         * Create test server
         */
        $server['HTTP_USER_AGENT'] = "Authentication Web Test Case";
        $server['REMOTE_ADDR'] = "127.0.0.1";

        $this->container->share('request', ServerRequestFactory::fromGlobals($server));

        $this->container->addServiceProvider('ServiceProvider\Redis');
        $this->container->addServiceProvider('ServiceProvider\Memcached');
        $this->container->addServiceProvider('ServiceProvider\Database');
        $this->container->addServiceProvider('ServiceProvider\Authentication');
    }
}
