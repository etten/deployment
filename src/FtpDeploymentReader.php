<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Server\Server;

class FtpDeploymentReader
{

	/** @var string */
	private $remoteBasePath;

	/** @var Server */
	private $server;

	public function __construct(string $remoteBasePath, Server $server)
	{
		$this->remoteBasePath = $remoteBasePath;
		$this->server = $server;
	}

	public function findDeployedFiles():array
	{
		$remotePath = $this->mergePaths($this->remoteBasePath, '/.htdeployment');

		if ($this->server->exists($remotePath)) {
			$tempFilePath = TempFile::create();
			$this->server->read($remotePath, $tempFilePath);
			return $this->read($tempFilePath);
		}

		return [];
	}

	private function read(string $file):array
	{
		$content = gzinflate(file_get_contents($file));

		$res = [];
		foreach (explode("\n", $content) as $item) {
			if (count($item = explode('=', $item, 2)) === 2) {
				$res[$item[1]] = $item[0] === '1' ? TRUE : $item[0];
			}
		}

		return $res;
	}

	private function mergePaths($path1, $path2)
	{
		return rtrim($path1, '/') . $path2;
	}

}
