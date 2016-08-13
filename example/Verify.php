<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>
<?php

include 'Header.php';

$identity = $container->get('Auth:Identity');

if ($request->getMethod() == 'POST') {
    $post = $request->getParsedBody();

    if (! $identity->isTemporary()) {
        echo '<span color="red">Your session has expired. Please <a href="/example/index.php">login</a> again.</span>';
        return;
    }
    if (isset($post['code']) && $post['code'] == 1234) {
        $identity->makePermanent(); // Make it permanent

        header("Location: /example/Restricted.php");
        return;
    } else {
        echo '<span color="red">Wrong code.</span>';
    }
}
?>
<h1>Verification Required</h1>

<p>To verify your account enter your verification code to below. Your identity will expire in 300 seconds. </p>

<span style="font-size:18px;color:gray">Test code: 1234</span>

<form name="verification" action="/example/Verify.php" method="POST" accept-charset="utf-8">  
    <p><label for="code">Code</label></p>
    <input type="text" id="code" name="code" placeholder="Code" required>
    <input type="submit" value="Login">
</form>
</body>
</html>