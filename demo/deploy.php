<?php

use Etten\Deployment;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
	'server' => [
		'host' => 'demo.hranicka.cz',
		'user' => 'demo.hranicka.cz',
		'password' => 'demo1.',
		'path' => '/',
		'secured' => FALSE,
	],
	'collector' => [
		'basePath' => __DIR__ . '/../',
		'ignore' => [],
	],
];

$server = new Deployment\FtpServer($config['server']);
$collector = new Deployment\FileCollector($config['collector']['basePath'], $config['collector']['ignore']);

$deployment = new Deployment\Deployment($server, $collector);
$deployment->run();
