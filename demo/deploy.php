<?php

use Etten\Deployment;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
	'server' => [
		'host' => 'demo.hranicka.cz',
		'user' => 'demo.hranicka.cz',
		'password' => 'demo1.',
		'secured' => FALSE,
	],
	'collector' => [
		'path' => __DIR__ . '/../',
		'ignore' => [],
	],
	'deployment' => [
		'path' => '/',
	],
];

$server = new Deployment\FtpServer($config['server']);
$collector = new Deployment\FileCollector($config['collector']);
$deployedList = new Deployment\FileList();

$deployment = new Deployment\Deployment($config['deployment'], $server, $collector, $deployedList);
$deployment->run();
