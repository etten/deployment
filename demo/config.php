<?php

namespace Etten\Deployment;

return [
	'deployer' => new Deployer(
		[
			'path' => '/',
			'temp' => '/.deploy/',
			'deployedFile' => '/.deployed',
			'deletedFile' => '/.deleted',
		],
		new Server\FtpServer([
			'host' => 'demo.hranicka.cz',
			'user' => 'demo.hranicka.cz',
			'password' => 'demo1.',
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
			'http://hranicka.cz/service/deploy?job=beforeMove',
		],
		'onFinish' => [
			'http://hranicka.cz/service/deploy?job=finish',
		],
	]),
];
