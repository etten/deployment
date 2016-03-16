<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

class FileRemoveJob extends ServerFileJob
{

	public function run()
	{
		$this->server->remove($this->source);
	}

}
