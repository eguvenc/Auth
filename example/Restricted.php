<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(dirname(__DIR__));
require_once "vendor/autoload.php";

session_start();

$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$container->share('request', $request);

$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');

$identity = $container->get('Auth:Identity');

/**
 * Don't forget check to user in your restricted pages !
 */
if ($identity->guest()) {
    header("Location: /example/index.php?error[]=Your session has expired.");
}
?>

<h2>User Identity</h2>

<pre><?php print_r($identity->getArray()) ?></pre>

<a href="/example/Logout.php?action=logout">Logout</a> ( Standart Logout ) | 
<a href="/example/Logout.php?action=destroy">Destroy</a> ( Destroy Cached Identity ) |
<a href="/example/Logout.php?action=forgetMe">Forget Me</a> ( Remove Me From This Computer )