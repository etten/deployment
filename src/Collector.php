<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

interface Collector
{

	/**
	 * @return string Full local base path.
	 */
	public function basePath():string;

	public function setLogger(Logger $logger);

	/**
	 * @return array [relativePath => hash]
	 */
	public function collect():array;

}
