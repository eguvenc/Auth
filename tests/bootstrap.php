<?php

// Prevent session cookies
ini_set('session.use_cookies', 0);

chdir(dirname(__DIR__));

// Enable Composer autoloader
$autoloader = require "vendor/autoload.php";

// Container
$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$container->share('request', $request);

$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');


// require dirname(__FILE__) . '/getallheaders.php';

// Register test classes
// $autoloader->addPsr4('Tests\\', __DIR__);