<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Deployment
{

	/** @var array */
	private $config = [
		'path' => '/',
		'temp' => '/.deploy/',
	];

	/** @var Server */
	private $server;

	/** @var Collector */
	private $collector;

	public function __construct(array $config, Server $server, Collector $collector)
	{
		$this->config = array_merge($this->config, $config);
		$this->server = $server;
		$this->collector = $collector;
	}

	public function run()
	{
		$files = $this->collector->collect();
		$this->upload($files);
	}

	private function upload(array $files)
	{
		foreach ($files as $file => $hash) {
			$this->server->write(
				$this->getRemotePath($file),
				$this->getLocalPath($file)
			);
		}
	}

	private function getRemoteBasePath()
	{
		return $this->config['path'];
	}

	private function getRemoteTempPath()
	{
		return $this->mergePaths($this->getRemoteBasePath(), $this->config['temp']);
	}

	private function getRemotePath(string $file):string
	{
		return $this->mergePaths($this->getRemoteTempPath(), $file);
	}

	private function getLocalPath(string $file):string
	{
		return $this->mergePaths($this->collector->basePath(), $file);
	}

	private function mergePaths($path1, $path2)
	{
		return rtrim($path1, '/') . $path2;
	}

}
