<html>
<head><style type="text/css">ul { list-style-type: none; } p { line-height: 2px; }</style></head>
<body>
<?php
include 'Header.php';

$identity = $container->get('Auth:Identity');

if (! isset($_SESSION['VerificationRequestLimit'])) { // Limit send code action
    $_SESSION['VerificationRequestLimit'] = 5;
}
if ($_SESSION['VerificationRequestLimit'] < 1) {
    unset($_SESSION['VerificationRequestLimit']);
    $identity->destroyTemporary();
    header('Location: /example/index.php?error[]=You reached to maximum request limit, your session has expired.');
    die;
}
if (false == $identity->isTemporary()) {
    header('Location: /example/index.php?error[]=Your session has expired.');
    die;
}
if ($request->getMethod() == 'GET') {
    $identity->makeTemporary(300);  // Refresh ttl
    $get = $request->getQueryParams();
    /**
     * User has 5 request credit to send new code request.
     * After 5 times we kill the session. Click f5 button 5 times and see the result.
     */
    $_SESSION['VerificationRequestLimit'] = $_SESSION['VerificationRequestLimit'] - 1;

    if (! empty($get['send_code'])) {
        echo "<span style=\"color:green;\">New code has been sent.</span>";
    }
}
?>
<h1>Verification Required</h1>

<p>To verify your account enter your verification code to below. Your session will expire in 300 seconds. </p>

<span style="font-size:18px;color:gray">Test code: 1234</span>

<form id="verify_form" action="/example/Verify.php" method="GET" accept-charset="utf-8">  
    <p><label for="code">Code</label></p>
    <input type="text" id="code" name="code" placeholder="Code" required>
</form>

<input type="button" value="Verify" onclick="verifyCode();">
<input type="button" name="send_code" value="Send Code Again" onclick="sendCode();" />

<span id="timer" style="display:block;margin-top:15px;font-size:18px;color:red;"></span>

<script type="text/javascript">   
var count = 299;
var counter = setInterval(timer, 1000); //1000 will  run it every 1 second
function timer() {
  count = count - 1;
  if (count <= 0) {
     clearInterval(counter);
     alert("Session end.");
     document.getElementById("timer").innerHTML = 0;
     // Counter ended, do something here
     return;
  }
  // Do code for showing the number of seconds here
  document.getElementById("timer").innerHTML = count;
}
</script>

<script type="text/javascript">
var myform = document.getElementById('verify_form');
var ajax = {
    post : function(url, closure, params){
    },
    get : function(url, closure, params) {
        var xmlhttp;
        xmlhttp = new XMLHttpRequest(); // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp.onreadystatechange=function(){
            if (xmlhttp.readyState==4 && xmlhttp.status==200){
                if( typeof closure === 'function'){
                    closure(xmlhttp.responseText);
                }
            }
        }
        xmlhttp.open("GET",url,true);
        xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xmlhttp.send(params);
    }
}
function verifyCode() {
    ajax.get('/example/VerifyCode.php?code=' + document.getElementById("code").value, function(json){
            var r = JSON.parse(json);
            if (r['success'] == 1) {
                window.location = "/example/Restricted.php";
            }
            if (r['success'] == 0) {
                if (r['code'] == '00') { // Expired code, redirect
                    window.location = "/example/index.php?errors[]=Your session has expired. Please login again.";
                }
                if (r['code'] == '01') {
                    alert(r['message']);
                }
            }
            console.log(r);
        },
        new FormData(myform)
    );
}
function sendCode() {
    var form = document.getElementById("verify_form");
    var input = document.createElement('input');
    input.type  = 'hidden';
    input.name  = 'send_code'
    input.value = '1';
    form.appendChild(input);
    return form.submit();
}
</script>

</body>
</html>