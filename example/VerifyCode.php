<?php
include 'Header.php';

$identity = $container->get('Auth:Identity');

$result = array();
$result['success'] = 0;

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

if ($request->getMethod() == 'GET') {
    $get = $request->getQueryParams();

    if (false == $identity->isTemporary()) {
        $result['code'] = '00';
        $result['message'] = 'Your session has expired. Please login again.';
        echo json_encode($message);
        return;
    }

    if (! empty($get['code']) && $get['code'] == 1234) {
        $identity->makePermanent(); // Make it permanent
        $result['success'] = 1;
    } else {
        $result['code'] = '01';
        $result['message'] = 'Wrong Code.';
    }
}

echo json_encode($result);
