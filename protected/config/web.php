<?php
return[
    'controllerNamespace' => 'app\controllers',
    'components' => [
        'request' => [
            'rules' => [
                'tentang' => ['site/page', 'page' => 'about'],
                'pages/{page}' => 'site/page',
            ],
            'cache' => true,
        ],
    ],
    'showScriptName' => true,
];
