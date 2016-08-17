<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>
<?php

include 'Header.php';

$identity = $container->get('Auth:Identity');
$storage  = $container->get('Auth:Storage');

/**
 * Remember User
 */
if ($token = $identity->hasRecallerCookie()) {
    $recaller = new Obullo\Auth\MFA\Recaller($container);
    $user = $recaller->recallUser($token);

    $authAdapter = new Obullo\Auth\MFA\Adapter\Database\Database($container);
    $authAdapter->setRequest($request);
    $authAdapter->regenerateSessionId(true);
    $authAdapter->authorizeUser($user);
}
/**
 * Temporary identity feature
 */
if ($identity->isTemporary()) {
    header("Location: /example/Verify.php");
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

<?php $sessions = $storage->getUserSessions();

// $storage->killSession('52c049faa3ef9f7407027b1a457f7982');
?>

<pre><?php print_r($sessions) ?></pre>
