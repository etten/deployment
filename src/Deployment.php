<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Exceptions\FtpException;

class Deployment
{

	/** @var array */
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
		$localFiles = $this->collector->collect();
		$deployedFiles = $this->readFileList($this->config['deployedFile']);

		$toUpload = $this->filterDeployedFiles($localFiles, $deployedFiles);
		$toDelete = array_diff_key($deployedFiles, $localFiles);

		$this->uploadFiles($toUpload);

		$this->writeFileList($this->config['deployedFile'], $localFiles);
		$this->writeFileList($this->config['deletedFile'], $toDelete);

		$this->moveFiles($toUpload);
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
		krsort($files); // Sort Z-A by $file - root directory at last

		foreach ($files as $file => $hash) {
			$isDir = substr($file, -1) === '/';

			if ($isDir) {
				// Create a new directory
				$this->server->write(
					$this->mergePaths($this->getRemoteBasePath(), $file),
					$this->mergePaths($this->collector->basePath(), $file)
				);

				// Remove temp directory (should be empty now)
				$this->server->remove(
					$this->mergePaths($this->getRemoteTempPath(), $file)
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

	private function readFileList(string $file):array
	{
		$tempFileStream = tmpfile();
		$tempFilePath = stream_get_meta_data($tempFileStream)['uri'];

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
		$tempFileStream = tmpfile();
		$tempFilePath = stream_get_meta_data($tempFileStream)['uri'];

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
