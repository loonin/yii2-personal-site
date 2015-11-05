<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
		'view' => [
			'defaultExtension' => 'twig',
			'class' => 'yii\web\View',
			'renderers' => [
				'twig' => [
					'class' => 'yii\twig\ViewRenderer',
					//'cachePath' => '@runtime/Twig/cache',
					//'options' => [], /*  Array of twig options */
					'globals' => ['html' => 'yii\helpers\Html'],
					'uses' => ['yii\bootstrap'],
					'options' => YII_DEBUG ? [
							'debug' => true,
							'auto_reload' => true,
						] : [],
					'extensions' => YII_DEBUG ? [
							'\Twig_Extension_Debug',
						] : [],
				],
			],
		],

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '000',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
		],
    ],
    'params' => $params,
	'aliases' => [
		'@twig' => '@vendor/twig',
	],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;
