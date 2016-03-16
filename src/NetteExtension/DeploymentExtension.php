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
		// Ignore invalid environments - we can be on production where file is not available
		$environments = array_filter($this->config['environments'], 'is_file');

		if ($environments) {
			foreach ($this->config['environments'] as $name => $path) {
				$environmentConfig = $this->loadFromFile($path)[$this->name];
				$config = DI\Config\Helpers::merge($this->getContainerBuilder()->expand($environmentConfig), $this->config);

				$this->addEnvironment($name, $config);
			}

		} else {
			$this->addEnvironment('', $this->config);
		}
	}

	private function addEnvironment(string $name, array $config)
	{
		$builder = $this->getContainerBuilder();

		$builder
			->addDefinition($this->prefixEnvironment('jobs', $name))
			->setClass(Deployment\Jobs\Jobs::class, [$this->buildJobs($name, $config)])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefixEnvironment('server', $name))
			->setClass(Deployment\Server\FtpServer::class, [$config['ftp']])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefixEnvironment('collector', $name))
			->setClass(Deployment\FileCollector::class, [
				[
					'path' => $config['paths']['local'],
					'ignore' => $config['paths']['ignore'],
				],
			])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefixEnvironment('fileList', $name))
			->setClass(Deployment\FileList::class)
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefixEnvironment('deployer', $name))
			->setClass(Deployment\Deployer::class, [
				[
					'path' => $config['paths']['remote'],
					'temp' => '/.deploy/',
					'deployedFile' => '/.deployed',
					'deletedFile' => '/.deleted',
				],
				'@' . $this->prefixEnvironment('server', $name),
				'@' . $this->prefixEnvironment('collector', $name),
				'@' . $this->prefixEnvironment('fileList', $name),
			])
			->setAutowired(FALSE);

		$builder
			->addDefinition($this->prefixEnvironment('deployment', $name))
			->setClass(Deployment\SymfonyConsole\DeploymentCommand::class, [
				implode(':', array_filter(['deployment', $name])),
			])
			->addSetup('setJobs', ['@' . $this->prefixEnvironment('jobs', $name)])
			->addSetup('setDeployer', ['@' . $this->prefixEnvironment('deployer', $name)])
			->addTag('kdyby.console.command');
	}

	private function prefixEnvironment(string $id, string $name)
	{
		$names = array_filter([$id, $name]);
		return $this->prefix(implode('.', $names));
	}

	private function buildJobs(string $environment, array $config)
	{
		$jobs = [];

		foreach ($config['jobs'] as $event => $list) {
			if (!is_array($list)) {
				continue;
			}

			foreach ($list as $job) {
				$jobs[$event][] = $this->expandJob($job, $environment, $config);
			}
		}

		return $jobs;
	}

	private function expandJob(string $job, string $environment, array $config):DI\Statement
	{
		$job = str_replace('HOST', $config['ftp']['host'], $job);
		$job = str_replace('USER', $config['ftp']['user'], $job);
		$job = str_replace('PASSWORD', $config['ftp']['password'], $job);

		if (preg_match('~^https?://.+~', $job)) {
			return new DI\Statement(Deployment\Jobs\GetRequestJob::class, [$job]);
		}

		if (preg_match('~^rename (.+?) (.+?)$~', $job, $m)) {
			return new DI\Statement(Deployment\Jobs\FileRenameJob::class, [
				'@' . $this->prefixEnvironment('server', $environment),
				$m[1],
				$m[2],
			]);
		}

		if (preg_match('~^remove (.+?) (.+?)$~', $job, $m)) {
			return new DI\Statement(Deployment\Jobs\FileRenameJob::class, [
				'@' . $this->prefixEnvironment('server', $environment),
				$m[1],
			]);
		}

		throw new \RuntimeException('Job was not recognized.');
	}

}
