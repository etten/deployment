<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Deployer
{

	/** @var string[] */
	private $config = [
		'temp' => '/.deploy/',
		'deployedFile' => '/.deployed',
		'deletedFile' => '/.deleted',
		'deployScript' => __DIR__ . '/../scripts/.deploy.php',
	];

	/** @var Server\Server */
	private $server;

	/** @var Collector */
	private $collector;

	/** @var FileList */
	private $fileList;

	/** @var Progress */
	private $progress;

	public function __construct(
		array $config,
		Server\Server $server,
		Collector $collector,
		FileList $fileList
	) {
		$this->config = array_merge($this->config, $config);
		$this->server = $server;
		$this->collector = $collector;
		$this->fileList = $fileList;
		$this->progress = new VoidProgress();
	}

	public function setProgress(Progress $progress)
	{
		$this->progress = $progress;
		$this->collector->setProgress($progress);
	}

	public function checkPrevious()
	{
		if ($this->server->exists($this->config['temp'])) {
			throw new Exception('Another deployment is in progress or has failed.');
		}
	}

	public function findLocalFiles():array
	{
		return $this->collector->collect();
	}

	public function findDeployedFiles():array
	{
		if ($this->server->exists($this->config['deployedFile'])) {
			$tempFilePath = TempFile::create();
			$this->server->read($this->config['deployedFile'], $tempFilePath);
			return $this->fileList->read($tempFilePath);
		}

		// dg/ftp-deployment compatibility
		$ftpDeployment = new FtpDeploymentReader($this->server);
		return $ftpDeployment->findDeployedFiles();
	}

	public function filterFilesToDeploy(array $local, array $deployed):array
	{
		return array_filter($local, function ($value, $key) use ($deployed) {
			if (!isset($deployed[$key])) {
				return TRUE;
			}

			return $value !== $deployed[$key];
		}, ARRAY_FILTER_USE_BOTH);
	}

	public function filterFilesToDelete(array $local, array $deployed):array
	{
		return array_diff_key($deployed, $local);
	}

	public function uploadFiles(array $files)
	{
		$count = count($files);
		$progress = 0;

		foreach ($files as $file => $hash) {
			$this->progress->log(sprintf('Uploading [%d/%d] %s', ++$progress, $count, $file));
			$this->server->write(
				$this->getTempPath($file),
				$this->mergePaths($this->collector->basePath(), $file)
			);
		}
	}

	public function moveFiles(array $files)
	{
		krsort($files); // Sort Z-A by file name - file before directory

		$count = count($files);
		$progress = 0;

		foreach ($files as $file => $hash) {
			$this->progress->log(sprintf('Moving [%d/%d] %s', ++$progress, $count, $file));

			$this->server->rename(
				$this->getTempPath($file),
				$file
			);
		}
	}

	public function deleteFiles(array $files)
	{
		$count = count($files);
		$progress = 0;

		foreach ($files as $file => $hash) {
			$this->progress->log(sprintf('Deleting [%d/%d] %s', ++$progress, $count, $file));
			$this->server->remove($file);
		}
	}

	public function clean()
	{
		// Clean .deploy/.deployed file if has not been removed.
		$this->server->remove(
			$this->getTempPath($this->config['deployedFile'])
		);

		// Clean .deploy/.deleted file if has not been removed.
		$this->server->remove(
			$this->getTempPath($this->config['deletedFile'])
		);

		// Try clean whole .deploy directory
		$this->server->remove($this->config['temp']);
	}

	public function writeDeployedList(array $files)
	{
		$this->writeFileList($this->config['deployedFile'], $files);
	}

	public function moveDeployedList()
	{
		$this->server->rename(
			$this->getTempPath($this->config['deployedFile']),
			$this->config['deployedFile']
		);
	}

	public function writeDeletedList(array $files)
	{
		$this->writeFileList($this->config['deletedFile'], $files);
	}

	public function writeDeployScript()
	{
		$scriptName = substr($this->config['deployScript'], strrpos($this->config['deployScript'], '/'));
		$this->server->write(
			$scriptName,
			$this->config['deployScript']
		);
	}

	private function writeFileList(string $file, array $files)
	{
		$tempFilePath = TempFile::create();
		$this->fileList->write($tempFilePath, $files);

		$this->server->write(
			$this->getTempPath($file),
			$tempFilePath
		);
	}

	private function getTempPath(string $path):string
	{
		return $this->mergePaths($this->config['temp'], $path);
	}

	private function mergePaths(string $path1, string $path2)
	{
		return rtrim($path1, '/') . $path2;
	}

}
