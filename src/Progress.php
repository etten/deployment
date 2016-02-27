<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

interface Progress
{

	public function log(string $message);

	public function ask(string $message, bool $default = TRUE):bool;

}
