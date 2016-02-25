<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Events
{

	/** @var array */
	public $onStart = [];

	/** @var array */
	public $onBeforeUpload = [];

	/** @var array */
	public $onBeforeMove = [];

	/** @var array */
	public $onFinish = [];

	public function start()
	{
		$this->trigger($this->onFinish);
	}

	public function beforeUpload()
	{
		$this->trigger($this->onBeforeUpload);
	}

	public function beforeMove()
	{
		$this->trigger($this->onBeforeMove);
	}

	public function finish()
	{
		$this->trigger($this->onFinish);
	}

	private function trigger(array $runners)
	{
		foreach ($runners as $runner) {
			if (is_callable($runner)) {
				$runner();

			} elseif (preg_match('~^https?://.+~', $runner)) {
				file_get_contents($runner);

			} else {
				throw new \RuntimeException('Cannot trigger Event. Unsupported runner given.');
			}
		}
	}

}
