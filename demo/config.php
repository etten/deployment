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
		new FtpServer([
			'host' => 'demo.hranicka.cz',
			'user' => 'demo.hranicka.cz',
			'password' => 'demo1.',
			'secured' => FALSE,
		]),
		new FileCollector([
			'path' => __DIR__ . '/../',
			'ignore' => [],
		]),
		new FileList()
	),
	'events' => new Events\Events([
		'onStart' => [],
		'onBeforeUpload' => [],
		'onBeforeMove' => [
			'http://hranicka.cz/service/deploy?event=beforeMove',
		],
		'onFinish' => [
			'http://hranicka.cz/service/deploy?event=finish',
		],
	]),
];
