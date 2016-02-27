<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Events;

use Etten\Deployment\Progress;
use Etten\Deployment\VoidProgress;

class Events
{

	/** @var Job[] */
	public $onStart = [];

	/** @var Job[] */
	public $onBeforeUpload = [];

	/** @var Job[] */
	public $onBeforeMove = [];

	/** @var Job[] */
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

	private function invoke(array $jobs)
	{
		foreach ($jobs as $job) {
			try {
				$this->runJob($job);

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
	 * @param Job|mixed $job
	 * @throws EventException
	 */
	private function runJob($job)
	{
		if (!$job instanceof Job) {
			$job = $this->createJob($job);
		}

		$this->progress->log(sprintf('Running job: %s', $job->getName()));

		try {
			$job->run();
		} catch (\Throwable $e) {
			throw new EventException($e->getMessage(), NULL, $e);
		}
	}

	private function createJob($input):Job
	{
		if (preg_match('~^https?://.+~', $input)) {
			return new GetRequestJob($input);
		}

		throw new EventException('Cannot create a Job instance.');
	}

}
