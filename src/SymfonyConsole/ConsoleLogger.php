<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\SymfonyConsole;

use Etten\Deployment\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger implements Logger
{

	/** @var OutputInterface */
	private $output;

	public function __construct(OutputInterface $output)
	{
		$this->output = $output;
	}

	public function log(string $message)
	{
		$this->output->writeln($message);
	}

}
