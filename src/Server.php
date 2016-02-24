<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

interface Server
{

	public function connect();

	public function read(string $remotePath, string $localPath);

	public function write(string $remotePath, string $localPath);

	public function remove(string $remotePath);

}
