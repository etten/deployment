<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\SymfonyConsole;

use Etten\Deployment;
use Symfony\Component\Console;

class DeploymentCommand extends Console\Command\Command
{

	/** @var Deployment\Events\Events */
	private $events;

	/** @var Deployment\Deployer */
	private $deployer;

	/**
	 * @param Deployment\Events\Events $events
	 * @return $this
	 */
	public function setEvents(Deployment\Events\Events $events)
	{
		$this->events = $events;
		return $this;
	}

	/**
	 * @param Deployment\Deployer $deployer
	 * @return $this
	 */
	public function setDeployer(Deployment\Deployer $deployer)
	{
		$this->deployer = $deployer;
		return $this;
	}

	protected function configure()
	{
		$this
			->setName('deployment')
			->setDescription('Deploys the application on remote server given by config.')
			->addOption('config', 'c', Console\Input\InputOption::VALUE_REQUIRED, 'Path to config file.')
			->addOption('list', 'l', Console\Input\InputOption::VALUE_NONE, 'Returns only list of files to upload and delete.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$configFile = $input->getOption('config');
		if ($configFile) {
			$this->loadConfigFromPhpFile($configFile);
		}

		$this->validateState();

		$progress = new Progress($this, $input, $output);
		$this->events->setProgress($progress);
		$this->deployer->setProgress($progress);

		$deployment = new Deployment\Runner(
			$progress,
			$this->events,
			$this->deployer
		);

		$deployment->setListOnly($input->getOption('list'));

		$deployment->run();
	}

	private function loadConfigFromPhpFile($file)
	{
		$config = require $file;
		$this->loadConfig($config);
	}

	private function loadConfig(array $config)
	{
		if (isset($config['events']) && $config['events'] instanceof Deployment\Events\Events) {
			$this->setEvents($config['events']);
		}

		if (isset($config['deployer']) && $config['deployer'] instanceof Deployment\Deployer) {
			$this->setDeployer($config['deployer']);
		}
	}

	private function validateState()
	{
		if (!$this->events || !$this->deployer) {
			throw new \RuntimeException('Config is not correct or has not been set.');
		}
	}

}
