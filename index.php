<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "vendor/autoload.php";

session_start();

$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$container->share('request', $request);

$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');

// End of the service
//------------------------------------------------------------//

$adapter = $container->get('auth:adapter');
$adapter->setRegenerateSessionId(true);
$adapter->setAuthenticate(true);

$credentials = new Obullo\Authentication\Credentials;
$credentials->setIdentityValue('user@example.com');
$credentials->setPasswordValue('123456');
$credentials->setRememberMeValue(false);

// Perform the authentication query, saving the result

$result = $adapter->login($credentials);

if (! $result->isValid()) {

    var_dump($result->getMessages());

} else {

    // var_dump($container->get('auth:identity')->destroy());
    var_dump($container->get('auth:identity')->getArray());

    // $row = $authAdapter->getResultRow();
}


// Print the result row:
// print_r($authAdapter->getResultRowObject());