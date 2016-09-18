<?php

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Interop\Container\ContainerInterface as Container;

/**
 * Sample Application
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class App
{
    protected $done;
    protected $container;

    /**
     * Constructor
     *
     * @param container $container container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->done = function ($request, $response, $err = null) {
            return $response;
        };
    }

    /**
     * Invoke application
     *
     * @param Request  $request  request
     * @param Response $response response
     *
     * @return void
     */
    public function __invoke(Request $request, Response $response)
    {
        $done = $this->done;
        
        return $done($request, $response, null);
    }


    /**
     * Returns to container
     *
     * @return object
     */
    public function getContainer()
    {
        return $this->container;
    }
}
