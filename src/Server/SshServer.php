<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

class SshServer extends ServerProxy
{

	/** @var array */
	protected $config = [
		'host' => NULL,
		'port' => 22,
		'user' => NULL,
		'password' => NULL,
		'path' => '/',
	];

	/** @var SshServer */
	protected $server;

	protected function createServer():Server
	{
		return new SshServerCore($this->config);
	}

	/**
	 * Executes command on remote server.
	 * @param string $command
	 * @return string
	 */
	public function exec(string $command)
	{
		return $this->server->exec($command);
	}

}
