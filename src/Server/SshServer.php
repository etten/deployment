<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

class SshServer extends ServerProxy
{

	/** @var array */
	private $config = [
		'host' => NULL,
		'port' => 22,
		'user' => NULL,
		'password' => NULL,
		'path' => '/',
	];

	protected function createServer():Server
	{
		return new SshServerCore($this->config);
	}

}
