<?php
require __DIR__ . '/system/Dee.php';

$config = require(__DIR__ . '/protected/config/main.php');
(new DApp($config))->run();
