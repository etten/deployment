<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Progress;

abstract class AbstractJob implements Job
{

	/** @var Progress */
	protected $progress;

	public function setProgress(Progress $progress)
	{
		$this->progress = $progress;
	}

}
