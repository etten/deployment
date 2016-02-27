<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\SymfonyConsole;

use Etten\Deployment;
use Symfony\Component\Console;

class Progress implements Deployment\Progress
{

	/** @var Console\Command\Command */
	private $command;

	/** @var Console\Input\InputInterface */
	private $input;

	/** @var Console\Output\OutputInterface */
	private $output;

	public function __construct(
		Console\Command\Command $command,
		Console\Input\InputInterface $input,
		Console\Output\OutputInterface $output
	) {
		$this->command = $command;
		$this->input = $input;
		$this->output = $output;
	}

	public function log(string $message)
	{
		$this->output->writeln($message);
	}

	public function ask(string $message, bool $default = TRUE):bool
	{
		sleep(1); // Workaround for Symfony QuestionHelper concurrency.
		$helper = $this->command->getHelper('question');
		$question = new Console\Question\ConfirmationQuestion($message, $default);
		return $helper->ask($this->input, $this->output, $question);
	}

}
