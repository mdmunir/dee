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
        'view' => [
            'packages' => [
                'bootstrap' => [
                    'css' => ['//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css']
                ],
                'bootstrap-plugin'=>[
                    'js' => ['//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'],
                    'depends' => [
                        'jquery',
                        'bootstrap'
                    ]
                ]
            ]
        ]
    ],
    'showScriptName' => true,
];
