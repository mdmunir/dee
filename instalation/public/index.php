<?php
require __DIR__ . '/../framework/Dee.php';

$config = array_merge(require(__DIR__ . '/../protected/config/main.php'),
    require(__DIR__ . '/../protected/config/web.php'));

$app = new dee\base\Application($config);
$app->run();
