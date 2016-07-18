<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Server\Server;

class FtpDeploymentReader
{

	/** @var Server */
	private $server;

	public function __construct(Server $server)
	{
		$this->server = $server;
	}

	public function findDeployedFiles() :array
	{
		if ($this->server->exists('/.htdeployment')) {
			$tempFilePath = TempFile::create();
			$this->server->read('/.htdeployment', $tempFilePath);
			return $this->read($tempFilePath);
		}

		return [];
	}

	private function read(string $file) :array
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

}
