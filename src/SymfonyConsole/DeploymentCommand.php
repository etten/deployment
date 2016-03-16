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

	/** @var Deployment\Jobs\Jobs */
	private $jobs;

	/** @var Deployment\Deployer */
	private $deployer;

	public function __construct($name = 'deployment')
	{
		parent::__construct($name);
	}

	/**
	 * @param Deployment\Jobs\Jobs $jobs
	 * @return $this
	 */
	public function setJobs(Deployment\Jobs\Jobs $jobs)
	{
		$this->jobs = $jobs;
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
			->setDescription('Deploys the application on remote server given by config.')
			->addOption('config', 'c', Console\Input\InputOption::VALUE_REQUIRED, 'Path to config file.')
			->addOption('test', 't', Console\Input\InputOption::VALUE_NONE, 'Does not really upload or delete files, just gets list of them.')
			->addOption('force', 'f', Console\Input\InputOption::VALUE_NONE, 'Force deploy. When another is in progress, continue anyway.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$configFile = $input->getOption('config');
		if ($configFile) {
			$this->loadConfigFromPhpFile($configFile);
		}

		$this->validateState();

		$progress = new Progress($this, $input, $output);
		$this->jobs->setProgress($progress);
		$this->deployer->setProgress($progress);

		$deployment = new Deployment\Runner(
			$progress,
			$this->jobs,
			$this->deployer
		);

		$deployment->setTestOnly($input->getOption('test'));
		$deployment->setForced($input->getOption('force'));

		$deployment->run();
	}

	private function loadConfigFromPhpFile($file)
	{
		$config = require $file;
		$this->loadConfig($config);
	}

	private function loadConfig(array $config)
	{
		if (isset($config['jobs']) && $config['jobs'] instanceof Deployment\Jobs\Jobs) {
			$this->setJobs($config['jobs']);
		}

		if (isset($config['deployer']) && $config['deployer'] instanceof Deployment\Deployer) {
			$this->setDeployer($config['deployer']);
		}
	}

	private function validateState()
	{
		if (!$this->jobs || !$this->deployer) {
			throw new \RuntimeException('Config is not correct or has not been set.');
		}
	}

}
