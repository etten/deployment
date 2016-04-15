<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

class CallbackJob extends AbstractJob
{

	/** @var string */
	private $name;

	/** @var \Closure */
	private $closure;

	public function __construct(string $name, \Closure $closure)
	{
		$this->name = $name;
		$this->closure = $closure;
	}

	public function getName():string
	{
		return $this->name;
	}

	public function run()
	{
		call_user_func($this->closure, $this->progress);
	}

}
