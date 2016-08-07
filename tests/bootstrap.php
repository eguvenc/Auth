<?php

// Prevent session cookies
ini_set('session.use_cookies', 0);
session_start();

chdir(dirname(__DIR__));

// Enable Composer autoloader
$autoloader = require "vendor/autoload.php";

// require dirname(__FILE__) . '/getallheaders.php';

// Register test classes
// $autoloader->addPsr4('Tests\\', __DIR__);