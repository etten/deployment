<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Deployment
{

	/** @var Server */
	private $server;

	/** @var Collector */
	private $collector;

	public function __construct(Server $server, Collector $collector)
	{
		$this->server = $server;
		$this->collector = $collector;
	}

	public function run()
	{
		$this->server->connect();

		$files = $this->collector->collect();

		foreach ($files as $file => $hash) {
			$this->server->write($file, $this->collector->basePath() . $file);
		}
	}

}
