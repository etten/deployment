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
		$this->loadConfig($input->getArgument('config'));

		$this->deployer->checkPrevious();

		$output->writeln(sprintf('Started at %s', date('r')));
		$this->events->start();

		// Collect files
		$localFiles = $this->deployer->findLocalFiles();
		$output->writeln(sprintf('%d local files found.', count($localFiles)), $output::VERBOSITY_VERBOSE);

		$deployedFiles = $this->deployer->findDeployedFiles();
		$output->writeln(sprintf('%d deployed files found.', count($deployedFiles)), $output::VERBOSITY_VERBOSE);

		$toUpload = $this->deployer->filterFilesToDeploy($localFiles, $deployedFiles);
		$output->writeln(sprintf('%d files to upload.', count($toUpload)));

		$toDelete = $this->deployer->filterFilesToDelete($localFiles, $deployedFiles);
		$output->writeln(sprintf('%d files to delete.', count($toDelete)));

		// Upload all new files
		if ($toUpload) {
			$this->events->beforeUpload();
			$this->deployer->uploadFiles($toUpload);
		}
		$output->writeln(sprintf('%d files uploaded.', count($toUpload)));

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

		// Move Deployed File List
		if ($toUpload || $toDelete) {
			$this->deployer->moveDeployedList();
			$output->writeln('Uploaded files moved from temp to production.');
		}

		// Delete not tracked files
		if ($toDelete) {
			$this->deployer->deleteFiles($toDelete);
		}
		$output->writeln(sprintf('%d files deleted.', count($toDelete)));

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
