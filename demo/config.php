<?php

namespace Etten\Deployment;

$host = '';
$user = '';
$password = '';

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
			"https://$host/?etten-maintainer-job=disable",
		],
		'onFinish' => [
			"https://$host/?etten-maintainer-job=enable",
		],
	]),
];
