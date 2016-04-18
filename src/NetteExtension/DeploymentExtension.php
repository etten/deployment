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
		'ssh' => [
			'host' => NULL,
			'user' => NULL,
			'password' => NULL,
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
				// If extra config file not exists, skip it.
				if (!is_file($path)) {
					continue;
				}

				// Additional config section may not exist.
				$environmentConfig = $this->loadFromFile($path)[$this->name] ?? [];
				$environmentConfig = DI\Helpers::expand($environmentConfig, $this->getContainerBuilder()->parameters);
				$config = DI\Config\Helpers::merge($environmentConfig, $this->config);

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

		// Prefer SSH over FTP. But not both together.
		if ($this->isFtp($config)) {
			unset($config['ssh']);

			$builder
				->addDefinition($this->prefixEnvironment('server', $name))
				->setClass(
					Deployment\Server\FtpServer::class,
					[['path' => $config['paths']['remote']] + ($config['ftp'] ?? [])]
				)
				->setAutowired(FALSE);
		} else {
			unset($config['ftp']);

			$builder
				->addDefinition($this->prefixEnvironment('server', $name))
				->setClass(
					Deployment\Server\SshServer::class,
					[['path' => $config['paths']['remote']] + ($config['ssh'] ?? [])]
				)
				->setAutowired(FALSE);
		}

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
		$isFtp = $this->isFtp($config);

		$job = str_replace('HOST', $isFtp ? $config['ftp']['host'] : $config['ssh']['host'], $job);
		$job = str_replace('USER', $isFtp ? $config['ftp']['user'] : $config['ssh']['user'], $job);
		$job = str_replace('PASSWORD', $isFtp ? $config['ftp']['password'] : $config['ssh']['password'], $job);

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

		if (preg_match('~^remove (.+?)$~', $job, $m)) {
			return new DI\Statement(Deployment\Jobs\FileRemoveJob::class, [
				'@' . $this->prefixEnvironment('server', $environment),
				$m[1],
			]);
		}

		if (preg_match('~^ssh (.+?)$~', $job, $m)) {
			if ($isFtp) {
				throw new \RuntimeException('Cannot use SSH jobs when FTP is configured and SSH not. Remove FTP configuration or SSH-related jobs.');
			}

			return new DI\Statement(Deployment\Jobs\SshJob::class, [
				'@' . $this->prefixEnvironment('server', $environment),
				$m[1],
			]);
		}

		throw new \RuntimeException('Job was not recognized.');
	}

	private function isFtp(array $config):bool
	{
		return empty($config['ssh']['host']) && !empty($config['ftp']['host']);
	}

}
