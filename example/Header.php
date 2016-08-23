<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(dirname(__DIR__));
require_once "vendor/autoload.php";

register_shutdown_function(
    function () {
        session_write_close();
    }
);
session_name('sessions');
session_set_cookie_params(
    3600,
    '/',
    '',
    false,
    false
);
session_start();

$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$response = new Zend\Diactoros\Response;
$container->share('request', $request);

$container->addServiceProvider('ServiceProvider\Cookie');
$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Memcached');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');
