<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Exceptions\FtpException;

class Deployment
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
	}

	public function run()
	{
		// Collect files
		$localFiles = $this->collector->collect();
		$deployedFiles = $this->readDeployedFiles($this->config['deployedFile']);

		$toUpload = $this->filterDeployedFiles($localFiles, $deployedFiles);
		$toDelete = array_diff_key($deployedFiles, $localFiles);

		// Run upload
		$this->uploadFiles($toUpload);

		// Create & Upload File Lists
		$this->writeFileList($this->config['deployedFile'], $localFiles);
		$this->writeFileList($this->config['deletedFile'], $toDelete);

		// Move uploaded files
		$this->moveFiles($toUpload);

		// Move Deployed File List
		$this->server->rename(
			$this->mergePaths($this->getRemoteTempPath(), $this->config['deployedFile']),
			$this->mergePaths($this->getRemoteBasePath(), $this->config['deployedFile'])
		);

		// Clean .deploy directory
		$this->server->remove($this->getRemoteTempPath());
	}

	private function filterDeployedFiles(array $local, array $deployed):array
	{
		return array_filter($local, function ($value, $key) use ($deployed) {
			if (!isset($deployed[$key])) {
				return TRUE;
			}

			return $value !== $deployed[$key];
		}, ARRAY_FILTER_USE_BOTH);
	}

	private function uploadFiles(array $files)
	{
		foreach ($files as $file => $hash) {
			$this->server->write(
				$this->mergePaths($this->getRemoteTempPath(), $file),
				$this->mergePaths($this->collector->basePath(), $file)
			);
		}
	}

	private function moveFiles(array $files)
	{
		ksort($files); // Sort A-Z by file name - directory before file

		foreach ($files as $file => $hash) {
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

	private function readDeployedFiles(string $file):array
	{
		$tempFilePath = TempFile::create();

		try {
			$this->server->read(
				$this->mergePaths($this->getRemoteBasePath(), $file),
				$tempFilePath
			);

			return $this->fileList->read($tempFilePath);
		} catch (FtpException $e) {
			return [];
		}
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
