<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Server\Server;

abstract class ServerFileJob extends AbstractJob
{

	/** @var Server */
	protected $server;

	/** @var string */
	protected $source;

	public function __construct(Server $server, string $source)
	{
		$this->server = $server;
		$this->source = $source;
	}

	public function getName():string
	{
		return implode(' ', [
			preg_replace('~Job$~', '', (new \ReflectionClass($this))->getShortName()),
			$this->source,
		]);
	}

}
