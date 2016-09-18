<?php
include 'Header.php';

use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

$html = '<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>';

$parsedBody  = $request->getParsedBody();
$queryParams = $request->getQueryParams();

if (isset($parsedBody['email']) && isset($parsedBody['password'])) { // Perform the authentication query

    $rememberMe = isset($parsedBody['remember_me']) ? 1 : 0;

    $authAdapter = new Obullo\Auth\Adapter\Table($container);

    $credentials = new Obullo\Auth\User\Credentials;
    $credentials->setIdentityValue($parsedBody['email']);
    $credentials->setPasswordValue($parsedBody['password']);
    $credentials->setRememberMeValue($rememberMe);
    
    $authResult = $authAdapter->authenticate($credentials);
    $authAdapter->regenerateSessionId(true);

    if (! $authResult->isValid()) {
        $messages = array();
        foreach ($authResult->getMessages() as $msg) {
            $messages['error'][] = $msg;
        };
        $response = new RedirectResponse("/example/index.php?".http_build_query($messages));
    } else {
        if ($hash = $authAdapter->passwordNeedsRehash()) {
            // Set new user password to db
        }
        // $container->get('Auth:Identity')->makeTemporary();

        $response = new RedirectResponse("/example/Restricted.php");
    }
} else {
    $html.= '<h1>Login</h1>';

    if (! empty($queryParams['error'])) {
        foreach ($queryParams['error'] as $error) {
            $html.= '<div style="color:red;">'.htmlspecialchars($error).'</div>';
        }
    }
    $html.= '<form name="login" action="/example/index.php" method="POST" accept-charset="utf-8">  
        <p><label for="usermail">Email</label></p>
        <input type="email" name="email" placeholder="yourname@email.com" required>
        <p><label for="password">Password</label></p>
        <input type="password" name="password" placeholder="password" required>
        <p><label for="remember_me">Remember Me</label><input type="checkbox" name="remember_me" id="remember_me" value="1"></p>
        <input type="submit" value="Login">
    </form></body>
    </html>';

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