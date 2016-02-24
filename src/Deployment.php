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
		$deployedFiles = $this->readFileList();

		$this->uploadFiles($this->filterFiles($localFiles, $deployedFiles));
		$this->writeFileList($localFiles);
	}

	private function filterFiles(array $newFiles, array $existingFiles):array
	{
		return array_filter($newFiles, function ($value, $key) use ($existingFiles) {
			if (!isset($existingFiles[$key])) {
				return TRUE;
			}

			return $value !== $existingFiles[$key];
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

	private function readFileList():array
	{
		$tempFile = tmpfile();
		$tempFilePath = stream_get_meta_data($tempFile)['uri'];

		try {
			$this->server->read(
				$this->mergePaths($this->getRemoteBasePath(), $this->config['deployedFile']),
				$tempFilePath
			);

			return $this->fileList->read($tempFilePath);
		} catch (FtpException $e) {
			return [];
		}
	}

	private function writeFileList(array $files)
	{
		$tempFile = tmpfile();
		$tempFilePath = stream_get_meta_data($tempFile)['uri'];

		$this->fileList->write($tempFilePath, $files);

		$this->server->write(
			$this->mergePaths($this->getRemoteTempPath(), $this->config['deployedFile']),
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
