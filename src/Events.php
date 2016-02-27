<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Exceptions\EventException;

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

	/** @var Progress */
	private $progress;

	public function __construct(array $config = [])
	{
		$this->onStart = $config['onStart'] ?? [];
		$this->onBeforeUpload = $config['onBeforeUpload'] ?? [];
		$this->onBeforeMove = $config['onBeforeMove'] ?? [];
		$this->onFinish = $config['onFinish'] ?? [];
		$this->progress = new VoidProgress();
	}

	/**
	 * @param Progress $progress
	 * @return $this
	 */
	public function setProgress(Progress $progress)
	{
		$this->progress = $progress;
		return $this;
	}

	public function start()
	{
		$this->invoke($this->onStart);
	}

	public function beforeUpload()
	{
		$this->invoke($this->onBeforeUpload);
	}

	public function beforeMove()
	{
		$this->invoke($this->onBeforeMove);
	}

	public function finish()
	{
		$this->invoke($this->onFinish);
	}

	private function invoke(array $runners)
	{
		foreach ($runners as $runner) {
			try {
				$this->invokeRunner($runner);
			} catch (EventException $e) {
				$this->progress->log(sprintf('Job failed with message: %s', $e->getMessage()));
				$continue = $this->progress->ask('Continue anyway?', TRUE);
				if (!$continue) {
					throw $e;
				}
			}
		}
	}

	/**
	 * @param mixed $runner
	 * @throws EventException
	 */
	private function invokeRunner($runner)
	{
		if (is_callable($runner)) {
			try {
				$runner($this->progress);
			} catch (\Throwable $e) {
				throw new EventException($e->getMessage(), NULL, $e);
			}

		} elseif (preg_match('~^https?://.+~', $runner)) {
			$this->progress->log(sprintf('Running job: %s', $runner));

			$response = @file_get_contents($runner);
			if ($response === FALSE) {
				throw new EventException(sprintf('Request failed.'));
			}

		} else {
			throw new \RuntimeException('Cannot trigger Event. Unsupported runner given.');
		}
	}

}
