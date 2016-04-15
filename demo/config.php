<?php

namespace Etten\Deployment;

use Etten\Deployment\Jobs;

$host = '';
$user = '';
$password = '';
$path = '/';

// If you need old FTP instead of SSH: Uncomment this and comment SSH.
//$server = new Server\FtpServer([
//	'host' => $host,
//	'user' => $user,
//	'password' => $password,
//	'secured' => TRUE, // When TRUE, sometimes fails with: "Unable to build data connection: Operation not permitted" (Windows)
//	'path' => $path,
//]);

$server = new Server\SshServer([
	'host' => $host,
	'user' => $user,
	'password' => $password,
	'path' => $path,
]);

$collector = new FileCollector([
	'path' => __DIR__ . '/../',
	'ignore' => [],
]);

$deployer = new Deployer(
	[
		'temp' => '/.deploy/',
		'deployedFile' => '/.deployed',
		'deletedFile' => '/.deleted',
	],
	$server,
	$collector,
	new FileList()
);

$jobs = new Jobs\Jobs([
	'onStart' => [],
	'onBeforeUpload' => [],
	'onBeforeMove' => [
		new Jobs\GetRequestJob("https://$host/?etten-maintainer-job=disable"),
//		// Alternatively, you can call SSH commands.
//		new Jobs\SshJob($server, '/bin/php web/index.php deploy:disableApp'),
	],
	'onFinish' => [
		new Jobs\FileRenameJob($server, '/app/config/config.production.neon', '/app/config/config.local.neon'),
		new Jobs\GetRequestJob("https://$host/?etten-maintainer-job=enable"),
	],
]);

return [
	'deployer' => $deployer,
	'jobs' => $jobs,
];
