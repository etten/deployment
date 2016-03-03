<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\NetteExtension;

use Etten\Deployment;
use Nette\DI;

class DeploymentExtension extends DI\CompilerExtension
{

	protected $config = [
		'ftp' => [
			'host' => NULL,
			'user' => NULL,
			'password' => NULL,
			'secured' => TRUE,
		],
		'paths' => [
			'local' => '%rootDir%',
			'remote' => '/',
			'ignore' => [],
		],
		'jobs' => [
			'onStart' => [],
			'onBeforeUpload' => [],
			'onBeforeMove' => [],
			'onFinish' => [],
		],
	];

	public function loadConfiguration()
	{
		// Normalize config
		$this->config['jobs'] = array_map(function ($v) {
			return (array)$v;
		}, $this->config['jobs']);

		$builder = $this->getContainerBuilder();

		$builder
			->addDefinition($this->prefix('jobs'))
			->setClass(Deployment\Jobs\Jobs::class, [$this->config['jobs']])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefix('server'))
			->setClass(Deployment\Server\FtpServer::class, [$this->config['ftp']])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefix('collector'))
			->setClass(Deployment\FileCollector::class, [
				[
					'path' => $this->config['paths']['local'],
					'ignore' => $this->config['paths']['ignore'],
				],
			])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefix('fileList'))
			->setClass(Deployment\FileList::class)
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefix('deployer'))
			->setClass(Deployment\Deployer::class, [
				[
					'path' => $this->config['paths']['remote'],
					'temp' => '/.deploy/',
					'deployedFile' => '/.deployed',
					'deletedFile' => '/.deleted',
				],
				'@' . $this->prefix('server'),
				'@' . $this->prefix('collector'),
				'@' . $this->prefix('fileList'),
			])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefix('deployment'))
			->setClass(Deployment\SymfonyConsole\DeploymentCommand::class)
			->addSetup('setJobs', ['@' . $this->prefix('jobs')])
			->addSetup('setDeployer', ['@' . $this->prefix('deployer')])
			->addTag('kdyby.console.command');
	}

}
