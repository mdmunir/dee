<?php
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}
require_once __DIR__ . '/../framework/Dee.php';

$config = array_merge(require(__DIR__ . '/../protected/config/main.php'),
    require(__DIR__ . '/../protected/config/web.php'));

$app = new dee\base\Application($config);
$app->run();
