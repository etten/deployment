<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

class FtpServer implements Server
{

	/** @var array */
	private $config = [
		'host' => NULL,
		'port' => NULL,
		'user' => NULL,
		'password' => NULL,
		'secured' => TRUE,
		'passive' => TRUE,
		'path' => '/',
	];

	/** @var Server */
	private $server;

	public function __construct(array $config)
	{
		$this->config = array_merge($this->config, $config);
		$this->server = new FtpServerCore($this->config);
	}

	public function exists(string $remotePath):bool
	{
		return $this->server->exists(
			$this->getRemotePath($remotePath)
		);
	}

	public function read(string $remotePath, string $localPath)
	{
		return $this->server->read(
			$this->getRemotePath($remotePath),
			$localPath
		);
	}

	public function write(string $remotePath, string $localPath)
	{
		return $this->server->write(
			$this->getRemotePath($remotePath),
			$localPath
		);
	}

	public function rename(string $originalPath, string $newPath)
	{
		return $this->server->rename(
			$this->getRemotePath($originalPath),
			$this->getRemotePath($newPath)
		);
	}

	public function remove(string $remotePath)
	{
		return $this->server->remove(
			$this->getRemotePath($remotePath)
		);
	}

	private function getRemotePath(string $path):string
	{
		return rtrim($this->config['path'], '/') . $path;
	}

}
