<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Server\SshException;

class SilentSshJob extends SshJob
{

	public function run()
	{
		try {
			parent::run();
		} catch (SshException $e) {
			$this->progress->log($e->getMessage());
		}
	}

}
