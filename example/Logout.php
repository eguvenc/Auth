<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(dirname(__DIR__));
require_once "vendor/autoload.php";

session_start();

$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$container->share('Request', $request);

$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');

$identity = $container->get('Auth:Identity');

$queryParams = $request->getQueryParams();

if (! empty($queryParams['action'])) {
    switch ($queryParams['action']) {
        case 'logout':
            $identity->logout();   // Standart logout identity cache is available for query speed.
            break;
        
        case 'destroy':
            $identity->destroy();  // Destroy cached identity in memory.
            break;

        case 'forgetMe':
            $identity->destroy();  // Destroy cached identity in memory.
            $identity->forgetMe(); // Removes remember me cookie from computer.
            break;
    }
    header("Location: /example/index.php?error[]=You have successfully logged out.");
}
