<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Server\SshServer;

class SshJob extends AbstractJob
{

	/** @var SshServer */
	private $ssh;

	/** @var string */
	private $command;

	public function __construct(SshServer $ssh, string $command)
	{
		$this->ssh = $ssh;
		$this->command = $command;
	}

	public function getName() :string
	{
		return 'ssh ' . $this->command;
	}

	public function run()
	{
		$response = $this->ssh->exec($this->command);
		$this->progress->log($response);
	}

}
