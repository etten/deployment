<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

class FtpServer extends ServerProxy
{

	/** @var array */
	protected $config = [
		'host' => NULL,
		'port' => NULL,
		'user' => NULL,
		'password' => NULL,
		'secured' => TRUE,
		'passive' => TRUE,
		'path' => '/',
	];

	protected function createServer() :Server
	{
		return new FtpServerCore($this->config);
	}

}
