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
$storage  = $container->get('Auth:Storage');

/**
 * Temporary identity feature
 */
if ($identity->isTemporary()) {
    header("Location: /example/index.php?error[]=Your identity freezed for 300 seconds.After that the verification you can continue.");
    return;
}
/**
 * Don't forget check to guest users in your all pages !
 */
if ($identity->guest()) {
    header("Location: /example/index.php?error[]=Your session has expired.");
    return;
}
?>
<h2>User Identity</h2>

<pre><?php print_r($identity->getArray()) ?></pre>


<a href="/example/Logout.php?action=logout">Logout</a> ( Standart Logout ) | 
<a href="/example/Logout.php?action=destroy">Destroy</a> ( Destroy Cached Identity ) |
<a href="/example/Logout.php?action=forgetMe">Forget Me</a> ( Remove Me From This Computer )

<h2>User Sessions</h2>

<?php $sessions = $storage->getUserSessions(); ?>

<pre><?php print_r($sessions) ?></pre>
