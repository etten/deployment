<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Jobs;

use Etten\Deployment\Progress;
use Etten\Deployment\VoidProgress;

class Jobs
{

	/** @var Job[] */
	private $onStart = [];

	/** @var Job[] */
	private $onBeforeUpload = [];

	/** @var Job[] */
	private $onBeforeMove = [];

	/** @var Job[] */
	private $onRemote = [];

	/** @var Job[] */
	private $onFinish = [];

	/** @var Progress */
	private $progress;

	public function __construct(array $config = [])
	{
		$this->onStart = $config['onStart'] ?? [];
		$this->onBeforeUpload = $config['onBeforeUpload'] ?? [];
		$this->onBeforeMove = $config['onBeforeMove'] ?? [];
		$this->onRemote = $config['onRemote'] ?? [];
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

	public function remote()
	{
		$this->invoke($this->onRemote);
	}

	public function finish()
	{
		$this->invoke($this->onFinish);
	}

	public function hasRemote():bool
	{
		return !!$this->onRemote;
	}

	private function invoke($jobs)
	{
		foreach ($jobs as $job) {
			try {
				$this->runJob($job);

			} catch (JobException $e) {
				$this->progress->log(sprintf('Job failed with message: %s', $e->getMessage()));
				$continue = $this->progress->ask('Continue anyway?', TRUE);
				if (!$continue) {
					throw $e;
				}
			}
		}
	}

	/**
	 * @param Job $job
	 * @throws JobException
	 */
	private function runJob(Job $job)
	{
		// Kdyby\Events support
		if ($job instanceof \Closure) {
			$job = $job->__invoke();

			if (!$job) {
				return;
			}
		}

		// Convert to a Job instance.
		$this->progress->log(sprintf('Running job: %s', $job->getName()));

		try {
			$job->run();
		} catch (\Throwable $e) {
			throw new JobException($e->getMessage(), NULL, $e);
		}
	}

}
