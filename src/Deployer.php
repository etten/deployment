<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Exceptions\Exception;

class Deployer
{

	/** @var string[] */
	private $config = [
		'path' => '/',
		'temp' => '/.deploy/',
		'deployedFile' => '/.deployed',
		'deletedFile' => '/.deleted',
	];

	/** @var Server */
	private $server;

	/** @var Collector */
	private $collector;

	/** @var FileList */
	private $fileList;

	/** @var Progress */
	private $progress;

	public function __construct(
		array $config,
		Server $server,
		Collector $collector,
		FileList $fileList
	) {
		$this->config = array_merge($this->config, $config);
		$this->server = $server;
		$this->collector = $collector;
		$this->fileList = $fileList;
		$this->progress = new VoidProgress();
	}

	/**
	 * @param Progress $progress
	 * @return $this
	 */
	public function setProgress(Progress $progress)
	{
		$this->progress = $progress;
		$this->collector->setProgress($progress);
		return $this;
	}

	public function checkPrevious()
	{
		if ($this->server->exists($this->getRemoteTempPath())) {
			throw new Exception('Another deployment is in progress or has failed.');
		}
	}

	public function findLocalFiles():array
	{
		return $this->collector->collect();
	}

	public function findDeployedFiles():array
	{
		$remotePath = $this->mergePaths($this->getRemoteBasePath(), $this->config['deployedFile']);

		if ($this->server->exists($remotePath)) {
			$tempFilePath = TempFile::create();
			$this->server->read($remotePath, $tempFilePath);
			return $this->fileList->read($tempFilePath);
		}

		return [];
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

	public function filterFilesToDelete(array $local, $deployed):array
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
				$this->mergePaths($this->getRemoteTempPath(), $file),
				$this->mergePaths($this->collector->basePath(), $file)
			);
		}
	}

	public function moveFiles(array $files)
	{
		ksort($files); // Sort A-Z by file name - directory before file

		$count = count($files);
		$progress = 0;

		foreach ($files as $file => $hash) {
			$this->progress->log(sprintf('Moving [%d/%d] %s', ++$progress, $count, $file));
			$isDir = substr($file, -1) === '/';

			if ($isDir) {
				// Create a new directory
				$this->server->write(
					$this->mergePaths($this->getRemoteBasePath(), $file),
					$this->mergePaths($this->collector->basePath(), $file)
				);
			} else {
				// Rename file
				$this->server->rename(
					$this->mergePaths($this->getRemoteTempPath(), $file),
					$this->mergePaths($this->getRemoteBasePath(), $file)
				);
			}
		}
	}

	public function deleteFiles(array $files)
	{
		$count = count($files);
		$progress = 0;

		foreach ($files as $file => $hash) {
			$this->progress->log(sprintf('Deleting [%d/%d] %s', ++$progress, $count, $file));

			$this->server->remove(
				$this->mergePaths($this->getRemoteBasePath(), $file)
			);
		}
	}

	public function clean()
	{
		$this->server->remove($this->getRemoteTempPath());
	}

	public function writeDeployedList(array $files)
	{
		$this->writeFileList($this->config['deployedFile'], $files);
	}

	public function moveDeployedList()
	{
		$this->server->rename(
			$this->mergePaths($this->getRemoteTempPath(), $this->config['deployedFile']),
			$this->mergePaths($this->getRemoteBasePath(), $this->config['deployedFile'])
		);
	}

	public function writeDeletedList(array $files)
	{
		$this->writeFileList($this->config['deletedFile'], $files);
	}

	private function writeFileList(string $file, array $files)
	{
		$tempFilePath = TempFile::create();
		$this->fileList->write($tempFilePath, $files);

		$this->server->write(
			$this->mergePaths($this->getRemoteTempPath(), $file),
			$tempFilePath
		);
	}

	private function getRemoteBasePath()
	{
		return $this->config['path'];
	}

	private function getRemoteTempPath()
	{
		return $this->mergePaths($this->getRemoteBasePath(), $this->config['temp']);
	}

	private function mergePaths($path1, $path2)
	{
		return rtrim($path1, '/') . $path2;
	}

}
