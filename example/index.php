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

$parsedBody  = $request->getParsedBody();
$queryParams = $request->getQueryParams();

if (isset($parsedBody['email']) && isset($parsedBody['password'])) {  // Perform the authentication query

    $rememberMe = isset($parsedBody['remember_me']) ? 1 : 0;

    $authAdapter = $container->get('Auth:Adapter');
    $authAdapter->setRegenerateSessionId(true);
    $authAdapter->setAuthenticate(true);

    $credentials = new Obullo\Authentication\Credentials;
    $credentials->setIdentityValue($parsedBody['email']);
    $credentials->setPasswordValue($parsedBody['password']);
    $credentials->setRememberMeValue($rememberMe);

    $authResult = $authAdapter->login($credentials);

    if (! $authResult->isValid()) {
        $messages = array();
        foreach ($authResult->getMessages() as $msg) {
            $messages['error'][] = $msg;
        };
        header("Location: /example/index.php?".http_build_query($messages));
    } else {
        header("Location: /example/Restricted.php");
    }
} else {
?>
<h1>Login</h1>
<?php
if (! empty($queryParams['error'])) {
    foreach ($queryParams['error'] as $error) {
        echo '<div style="color:red;">'.htmlspecialchars($error).'</div>';
    }
}
?>
<form name="login" action="/example/index.php" method="POST" accept-charset="utf-8">  
    <p><label for="usermail">Email</label></p>
    <input type="email" name="email" placeholder="yourname@email.com" required>
    <p><label for="password">Password</label></p>
    <input type="password" name="password" placeholder="password" required>
    <p><label for="remember_me">Remember Me</label><input type="checkbox" name="remember_me" id="remember_me" value="1"></p>
    <input type="submit" value="Login">
</form>  
<?php
}
?>
</body>
</html>