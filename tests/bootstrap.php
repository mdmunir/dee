<?php
error_reporting(-1);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
// require composer autoloader if available
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

require_once __DIR__ . '/../framework/Dee.php';
Dee::setAlias('@tests', __DIR__);

require_once __DIR__ . '/compatibility.php';
require_once __DIR__ . '/TestCase.php';