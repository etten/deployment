<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Events;

class GetRequestJob implements Job
{

	/** @var string */
	private $url;

	public function __construct(string $url)
	{
		$this->url = $url;
	}

	public function getName():string
	{
		return $this->url;
	}

	public function run()
	{
		$response = @file_get_contents($this->url);
		if ($response === FALSE) {
			throw new EventException(sprintf('Request failed.'));
		}
	}

}
