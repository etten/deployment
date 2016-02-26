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

	/** @var Deployment\Logger */
	private $logger;

	/** @var Deployment\Events */
	private $events;

	/** @var Deployment\Deployer */
	private $deployer;

	protected function configure()
	{
		$this
			->setName('deployment')
			->setDescription('Deploys the application on remote server given by config.')
			->addArgument('config', Console\Input\InputArgument::REQUIRED, 'Path to Deployer factory file.');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$this->logger = new ConsoleLogger($output);
		$this->loadConfig($input->getArgument('config'));

		$this->deployer->checkPrevious();

		$this->logger->log(sprintf('Started at %s', date('r')));
		$this->logger->log('');
		$this->events->start();

		// Collect files
		$localFiles = $this->deployer->findLocalFiles();

		$this->logger->log(sprintf('%d local files and directories found.', count($localFiles)));

		$deployedFiles = $this->deployer->findDeployedFiles();
		$this->logger->log(sprintf('%d deployed files and directories found.', count($deployedFiles)));

		$toUpload = $this->deployer->filterFilesToDeploy($localFiles, $deployedFiles);
		$this->logger->log(sprintf('%d files and directories to upload.', count($toUpload)));

		$toDelete = $this->deployer->filterFilesToDelete($localFiles, $deployedFiles);
		$this->logger->log(sprintf('%d files and directories to delete.', count($toDelete)));

		$this->logger->log('');

		// Upload all new files
		if ($toUpload) {
			$this->events->beforeUpload();
			$this->deployer->uploadFiles($toUpload);
		}

		$this->logger->log(sprintf('%d files and directories uploaded.', count($toUpload)));
		$this->logger->log('');

		// Create & Upload File Lists
		if ($toUpload || $toDelete) {
			$this->deployer->writeDeployedList($localFiles);
		}

		if ($toDelete) {
			$this->deployer->writeDeletedList($toDelete);
		}

		// Move uploaded files
		if ($toUpload) {
			$this->events->beforeMove();
			$this->deployer->moveFiles($toUpload);
		}

		$this->logger->log(sprintf('%d files and directories moved from temp to production.', count($toUpload)));
		$this->logger->log('');

		// Move Deployed File List
		if ($toUpload || $toDelete) {
			$this->deployer->moveDeployedList();
		}

		// Delete not tracked files
		if ($toDelete) {
			$this->deployer->deleteFiles($toDelete);
		}

		$this->logger->log(sprintf('%d files and directories deleted.', count($toDelete)));
		$this->logger->log('');

		// Clean .deploy directory
		if ($toUpload || $toDelete) {
			$this->deployer->clean();
		}

		$this->events->finish();
	}

	private function loadConfig($file)
	{
		$config = (array)require $file;

		$this->events = $this->getConfigObject($config, 'events', Deployment\Events::class);

		$this->deployer = $this->getConfigObject($config, 'deployer', Deployment\Deployer::class);
		$this->deployer->setLogger($this->logger);
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
