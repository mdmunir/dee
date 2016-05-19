<?php
return[
    'controllerNamespace' => 'app\controllers',
    'components' => [
        'request' => [
            'rules' => [
                'pages/{page}' => 'site/page'
            ],
            'cache' => true,
        ],
    ],
    'showScriptName' => true,
];
