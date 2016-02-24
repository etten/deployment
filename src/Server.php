<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

interface Server
{

	public function read(string $remotePath, string $localPath);

	public function write(string $remotePath, string $localPath);

	public function rename(string $originalPath, string $newPath);

	public function remove(string $remotePath);

}
