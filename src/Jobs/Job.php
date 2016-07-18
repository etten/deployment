<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Progress;

interface Job
{

	public function setProgress(Progress $progress);

	public function getName() :string;

	public function run();

}
