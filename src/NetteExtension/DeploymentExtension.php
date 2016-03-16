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
		'environments' => [],
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
		$builder = $this->getContainerBuilder();

		if (!$this->config['environments']) {
			throw new \RuntimeException('No Environment is configured.');
		}

		foreach ($this->config['environments'] as $name => $path) {
			$environmentConfig = $this->loadFromFile($path)[$this->name];
			$config = DI\Config\Helpers::merge($this->getContainerBuilder()->expand($environmentConfig), $this->config);

			// Normalize & expand config
			$config['jobs'] = array_map(function ($v) {
				return $this->expandJobs((array)$v, $this->config);
			}, $config['jobs']);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'jobs'))
				->setClass(Deployment\Jobs\Jobs::class, [$config['jobs']])
				->setAutowired(FALSE);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'server'))
				->setClass(Deployment\Server\FtpServer::class, [$config['ftp']])
				->setAutowired(FALSE);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'collector'))
				->setClass(Deployment\FileCollector::class, [
					[
						'path' => $config['paths']['local'],
						'ignore' => $config['paths']['ignore'],
					],
				])
				->setAutowired(FALSE);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'fileList'))
				->setClass(Deployment\FileList::class)
				->setAutowired(FALSE);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'deployer'))
				->setClass(Deployment\Deployer::class, [
					[
						'path' => $config['paths']['remote'],
						'temp' => '/.deploy/',
						'deployedFile' => '/.deployed',
						'deletedFile' => '/.deleted',
					],
					'@' . $this->prefixEnvironment($name, 'server'),
					'@' . $this->prefixEnvironment($name, 'collector'),
					'@' . $this->prefixEnvironment($name, 'fileList'),
				])
				->setAutowired(FALSE);

			$builder
				->addDefinition($this->prefixEnvironment($name, 'deployment'))
				->setClass(Deployment\SymfonyConsole\DeploymentCommand::class, [
					'deployment:' . $name,
				])
				->addSetup('setJobs', ['@' . $this->prefixEnvironment($name, 'jobs')])
				->addSetup('setDeployer', ['@' . $this->prefixEnvironment($name, 'deployer')])
				->addTag('kdyby.console.command');
		}
	}

	private function prefixEnvironment($env, $id)
	{
		return $this->prefix($id . '.' . $env);
	}

	private function expandJobs(array $jobs, array $config)
	{
		return array_map(function ($job) use ($config) {
			$job = str_replace('HOST', $config['ftp']['host'], $job);
			$job = str_replace('USER', $config['ftp']['user'], $job);
			$job = str_replace('PASSWORD', $config['ftp']['password'], $job);

			return $job;
		}, $jobs);
	}

}
