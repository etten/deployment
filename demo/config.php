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
//		// If you can't run PHP via SSH, you can manage application via HTTP.
//		new Jobs\GetRequestJob("https://$host/?etten-maintainer-job=disable"),
		new Jobs\SshJob($server, "php {$path}web/index.php maintainer:disable"),
	],
	'onRemote' => [
		// This runs a remote script which moves and deletes files server-side (faster).
		new Jobs\SshJob($server, "php {$path}.deploy.php"),
	],
	'onFinish' => [
		new Jobs\FileRenameJob($server, '/app/config/config.production.neon', '/app/config/config.local.neon'),
//		// If you can't run PHP via SSH, you can manage application via HTTP.
//		new Jobs\GetRequestJob("https://$host/?etten-maintainer-job=enable"),
		new Jobs\SshJob($server, "php {$path}web/index.php maintainer:enable"),
	],
]);

return [
	'deployer' => $deployer,
	'jobs' => $jobs,
];
