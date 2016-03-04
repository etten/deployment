<?php

namespace Etten\Deployment;

$host = 'hranicka.cz';
$user = 'demo.hranicka.cz';
$password = 'demo1.';

return [
	'deployer' => new Deployer(
		[
			'path' => '/',
			'temp' => '/.deploy/',
			'deployedFile' => '/.deployed',
			'deletedFile' => '/.deleted',
		],
		new Server\FtpServer([
			'host' => $host,
			'user' => $user,
			'password' => $password,
			'secured' => TRUE,
		]),
		new FileCollector([
			'path' => __DIR__ . '/../',
			'ignore' => [],
		]),
		new FileList()
	),
	'jobs' => new Jobs\Jobs([
		'onStart' => [],
		'onBeforeUpload' => [],
		'onBeforeMove' => [
			"https://$host/service/deploy?job=beforeMove",
		],
		'onFinish' => [
			"https://$host/service/deploy?job=finish",
		],
	]),
];
