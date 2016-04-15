<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Server\Server;

class FileRenameJob extends ServerFileJob
{

	/** @var string */
	private $destination;

	public function __construct(Server $server, string $source, string $destination)
	{
		parent::__construct($server, $source);
		$this->destination = $destination;
	}

	public function getName():string
	{
		return implode(' ', [
			preg_replace('~Job$~', '', (new \ReflectionClass($this))->getShortName()),
			$this->source,
			$this->destination,
		]);
	}

	public function run()
	{
		$this->server->rename($this->source, $this->destination);
	}

}
