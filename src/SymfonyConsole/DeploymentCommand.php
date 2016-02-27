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

	/** @var Deployment\Progress */
	private $progress;

	/** @var \Etten\Deployment\Events\Events */
	private $events;

	/** @var Deployment\Deployer */
	private $deployer;

	/** @var bool */
	private $listOnly = FALSE;

	protected function configure()
	{
		$this
			->setName('deployment')
			->setDescription('Deploys the application on remote server given by config.')
			->addArgument('config', Console\Input\InputArgument::REQUIRED, 'Path to Deployer factory file.')
			->addOption('list', 'l', Console\Input\InputOption::VALUE_NONE, 'Returns only list of files to upload and delete.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->loadConfig($input);

		$deployment = new Deployment\Runner(
			new Progress($this, $input, $output),
			$this->events,
			$this->deployer
		);

		$deployment->setListOnly($this->listOnly);

		$deployment->run();
	}

	private function loadConfig(Console\Input\InputInterface $input)
	{
		$config = (array)require $input->getArgument('config');

		$this->events = $this->getConfigObject($config, 'events', Deployment\Events\Events::class);
		$this->events->setProgress($this->progress);

		$this->deployer = $this->getConfigObject($config, 'deployer', Deployment\Deployer::class);
		$this->deployer->setProgress($this->progress);

		$this->listOnly = (bool)$input->getOption('list');
	}

	private function getConfigObject(array $config, string $name, string $class)
	{
		$object = $config[$name] ?? NULL;
		if (!$object || !is_a($object, $class)) {
			throw new \RuntimeException(sprintf('Given config %s is not instance of %s.', $name, $class));
		}

		return $object;
	}

}
