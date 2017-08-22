<?php

include 'Header.php';

$identity = $container->get('Auth:Identity');

$queryParams = $request->getQueryParams();

if (! empty($queryParams['action'])) {
    switch ($queryParams['action']) {
        case 'logout':
            $identity->logout();   // Standart logout identity cache is available for query speed.
            break;
        
        case 'destroy':
            $identity->destroy();  // Destroy cached identity in memory.
            break;

        case 'forgetMe':
            $identity->destroy();  // Destroy cached identity in memory.
            $identity->forgetMe(); // Removes remember me cookie from computer.
            break;
    }
    header("Location: /example/index.php?error[]=You have successfully logged out.");
}
