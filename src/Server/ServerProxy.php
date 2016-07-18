<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

abstract class ServerProxy implements Server
{

	/** @var array */
	protected $config = [
		'path' => '/',
	];

	/** @var Server */
	protected $server;

	public function __construct(array $config)
	{
		$this->config = array_merge($this->config, $config);
		$this->server = $this->createServer();
	}

	public function exists(string $remotePath) :bool
	{
		return $this->server->exists(
			$this->getRemotePath($remotePath)
		);
	}

	public function read(string $remotePath, string $localPath)
	{
		$this->server->read(
			$this->getRemotePath($remotePath),
			$localPath
		);
	}

	public function write(string $remotePath, string $localPath)
	{
		$this->server->write(
			$this->getRemotePath($remotePath),
			$localPath
		);
	}

	public function rename(string $originalPath, string $newPath)
	{
		$this->server->rename(
			$this->getRemotePath($originalPath),
			$this->getRemotePath($newPath)
		);
	}

	public function remove(string $remotePath)
	{
		$this->server->remove(
			$this->getRemotePath($remotePath)
		);
	}

	abstract protected function createServer() :Server;

	protected function getRemotePath(string $path) :string
	{
		return rtrim($this->config['path'], '/') . $path;
	}

}
