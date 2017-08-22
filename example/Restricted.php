<?php
include 'Header.php';

$html = '<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>';

$identity = $container->get('Auth:Identity');

/**
 * Middleware: Recall User
 */
if ($token = $identity->hasRecallerCookie()) {
    $recaller = new Obullo\Auth\Recaller($container);
    
    if ($resultRowArray = $recaller->recallUser($token)) {
        $credentials = new Obullo\Auth\Credentials;
        $credentials->setIdentityValue($resultRowArray['email']);
        $credentials->setPasswordValue($resultRowArray['password']);
        $credentials->setRememberMeValue(true);

        $user = new Obullo\Auth\User($credentials);
        $user->setResultRow($resultRowArray);

        $authAdapter = new Obullo\Auth\AuthAdapter($container);
        $authAdapter->authorize($user);
        $authAdapter->regenerateSessionId(true);
    }
}
/**
 * Middleware: Guest
 */
if ($identity->guest()) {
    /**
     * Check Temporary identity
     */
    if ($identity->isTemporary()) {
        header("Location: /example/Verify.php");
        die;
    } else {
        header('Location: /example/index.php?error[]=Your session has expired.');
    }
}
/**
* Middleware: Auth
*/
if ($identity->check()) {
    $html.= '<h2>User Identity</h2>';
    $html.= '<pre>'.print_r($identity->getArray(), true).'</pre>';

    $html.= '<a href="/example/Logout.php?action=logout">Logout</a> ( Standart Logout ) | ';
    $html.= '<a href="/example/Logout.php?action=destroy">Destroy</a> ( Destroy Cached Identity ) | ';
    $html.= '<a href="/example/Logout.php?action=forgetMe">Forget Me</a> ( Remove Me From This Computer )';

    $html.= '<h2>User Sessions</h2>';
    $storage  = $container->get('Auth:Storage');
    $sessions = $storage->getUserSessions();
    // $storage->killSession('52c049faa3ef9f7407027b1a457f7982');

    $html.= '<pre>'.print_r($sessions, true).'</pre>';
    echo $html;
}
