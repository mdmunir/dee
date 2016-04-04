<?php
return[
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => [
            'dsn' => 'sqlite:@app/runtime/data.sql',
//            'username' => '',
//            'password' => ''
        ]
    ],
    'showScriptName' => true,
    'params' => [
    ]
];
