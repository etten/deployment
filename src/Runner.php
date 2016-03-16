<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Runner
{

	/** @var Progress */
	private $progress;

	/** @var Jobs\Jobs */
	private $jobs;

	/** @var Deployer */
	private $deployer;

	/** @var bool */
	private $testOnly = FALSE;

	/** @var bool */
	private $forced = FALSE;

	public function __construct(
		Progress $progress,
		Jobs\Jobs $jobs,
		Deployer $deployer
	) {
		$this->progress = $progress;
		$this->jobs = $jobs;
		$this->deployer = $deployer;
	}

	/**
	 * @param boolean $testOnly
	 * @return $this
	 */
	public function setTestOnly(bool $testOnly)
	{
		$this->testOnly = $testOnly;
		return $this;
	}

	/**
	 * @param boolean $forced
	 * @return $this
	 */
	public function setForced(bool $forced)
	{
		$this->forced = $forced;
		return $this;
	}

	public function run()
	{
		if (!$this->forced) {
			$this->deployer->checkPrevious();
		}

		$this->progress->log(sprintf('Started at %s', date('r')));
		$this->progress->log('');
		$this->jobs->start();

		// Collect files
		$localFiles = $this->deployer->findLocalFiles();

		$this->progress->log(sprintf('%d local files and directories found.', count($localFiles)));

		$deployedFiles = $this->deployer->findDeployedFiles();
		$this->progress->log(sprintf('%d deployed files and directories found.', count($deployedFiles)));

		$toUpload = $this->deployer->filterFilesToDeploy($localFiles, $deployedFiles);
		$this->progress->log(sprintf('%d files and directories to upload.', count($toUpload)));

		$toDelete = $this->deployer->filterFilesToDelete($localFiles, $deployedFiles);
		$this->progress->log(sprintf('%d files and directories to delete.', count($toDelete)));

		$this->progress->log('');

		// Show only the list?
		if ($this->testOnly) {
			$this->progress->log('SHOWING LIST OF FILES ONLY.');
			$this->progress->log('Nothing will be uploaded or deleted on the server.');
			$this->progress->log('');

			$this->showList($toUpload, $toDelete);
			return;
		}

		// Upload all new files
		if ($toUpload) {
			$this->jobs->beforeUpload();
			$this->deployer->uploadFiles($toUpload);
		}

		$this->progress->log(sprintf('%d files and directories uploaded.', count($toUpload)));
		$this->progress->log('');

		// Create & Upload File Lists
		if ($toUpload || $toDelete) {
			$this->deployer->writeDeployedList($localFiles);
		}

		if ($toDelete) {
			$this->deployer->writeDeletedList($toDelete);
		}

		// Move uploaded files
		if ($toUpload) {
			$this->jobs->beforeMove();
			$this->deployer->moveFiles($toUpload);
		}

		$this->progress->log(sprintf('%d files and directories moved from temp to production.', count($toUpload)));
		$this->progress->log('');

		// Move Deployed File List
		if ($toUpload || $toDelete) {
			$this->deployer->moveDeployedList();
		}

		// Delete not tracked files
		if ($toDelete) {
			$this->deployer->deleteFiles($toDelete);
		}

		$this->progress->log(sprintf('%d files and directories deleted.', count($toDelete)));
		$this->progress->log('');

		// Clean .deploy directory
		if ($toUpload || $toDelete) {
			$this->deployer->clean();
		}

		$this->jobs->finish();
	}

	private function showList(array $toUpload, array $toDelete)
	{
		foreach ($toUpload as $file => $hash) {
			$this->progress->log(sprintf('To upload: %s', $file));
		}

		$this->progress->log('');

		foreach ($toDelete as $file => $hash) {
			$this->progress->log(sprintf('To delete: %s', $file));
		}

		$this->progress->log('');
	}

}
