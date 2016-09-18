<?php
include 'Header.php';

use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

$html = '<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>';

$identity = $container->get('Auth:Identity');
$storage  = $container->get('Auth:Storage');


/**
 * Middleware: Recall User
 */
if ($token = $identity->hasRecallerCookie()) {

    $recaller = new Obullo\Auth\Recaller($container);
    
    if ($user = $recaller->recallUser($token)) {
        $authAdapter = new Obullo\Auth\Adapter\Table($container);
        $authAdapter->authorizeUser($user);
        $authAdapter->regenerateSessionId(true);
    }
}
/**
 * Middleware: Temporary identity
 */
if ($identity->isTemporary()) {
    $response = new RedirectResponse("/example/Verify.php");
}
/**
* Middleware: Guest identity
*/
if ($identity->guest()) {
    $response = new RedirectResponse("/example/index.php?error[]=Your session has expired.");
}
/**
* Middleware: Auth identity
*/
if ($identity->check()) {
    $html.= '<h2>User Identity</h2>';
    $html.= '<pre>'.print_r($identity->getArray(), true).'</pre>';

    $html.= '<a href="/example/Logout.php?action=logout">Logout</a> ( Standart Logout ) | ';
    $html.= '<a href="/example/Logout.php?action=destroy">Destroy</a> ( Destroy Cached Identity ) | ';
    $html.= '<a href="/example/Logout.php?action=forgetMe">Forget Me</a> ( Remove Me From This Computer )';

    $html.= '<h2>User Sessions</h2>';
    $sessions = $storage->getUserSessions();
    // $storage->killSession('52c049faa3ef9f7407027b1a457f7982');

    $html.= '<pre>'.print_r($sessions, true).'</pre>';
    $response = new HtmlResponse($html);
}
/**
 * Create server
 */
$server = Zend\Diactoros\Server::createServerfromRequest(
    new App($container),
    $request,
    $response
);
/**
 * Emit response
 */
$server->listen();
