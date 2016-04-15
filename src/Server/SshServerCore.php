<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

class SshServerCore implements Server
{

	/** @var array */
	private $config = [
		'host' => NULL,
		'port' => 22,
		'user' => NULL,
		'password' => NULL,
	];

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $sftp;

	public function __construct(array $config)
	{
		$this->config = array_merge($this->config, $config);
	}

	public function exists(string $remotePath):bool
	{
		return file_exists($this->sftpPath($remotePath));
	}

	public function read(string $remotePath, string $localPath)
	{
		$this->ssh('scp_recv', [$remotePath, $localPath]);
	}

	public function write(string $remotePath, string $localPath)
	{
		$this->ssh('scp_send', [$localPath, $remotePath]);
	}

	public function rename(string $originalPath, string $newPath)
	{
		$this->exec('mv -f ' . $this->escape($originalPath) . ' ' . $this->escape($newPath));
	}

	public function remove(string $remotePath)
	{
		$this->exec('rm -rf ' . $this->escape($remotePath));
	}

	/**
	 * @param string $command SSH command name
	 * @param array $args
	 * @return mixed
	 * @throws SshException
	 */
	private function ssh(string $command, array $args = [])
	{
		$this->connect();

		array_unshift($args, $this->connection);
		return $this->protect('ssh2_' . $command, $args);
	}

	private function exec(string $command):string
	{
		$stream = $this->ssh('exec', [$command]);
		stream_set_blocking($stream, TRUE);
		$streamOut = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
		return stream_get_contents($streamOut);
	}

	private function escape(string $command):string
	{
		return escapeshellcmd($command);
	}

	private function sftpPath(string $path):string
	{
		$this->connect();

		if (!$this->sftp) {
			$this->sftp = $this->protect('sftp');
		}

		return 'ssh2.sftp://' . $this->sftp . $path;
	}

	private function connect()
	{
		if ($this->connection) {
			return;
		}

		if (!extension_loaded('ssh2')) {
			throw new SshException('PHP extension SSH2 is not loaded.');
		}

		if (!$this->config['host']) {
			throw new SshException('HOST is not set.');
		}

		$this->connection = $this->protect('ssh2_connect', [$this->config['host'], $this->config['port']]);
		$this->ssh('auth_password', [$this->config['user'], $this->config['password']]);
	}

	/**
	 * @param string $command
	 * @param array $args
	 * @return mixed
	 * @throws SshException
	 */
	private function protect(string $command, array $args = [])
	{
		$errorHandler = function ($severity, $message) {
			if (preg_match('~^\w+\(\):\s*(.+)~', $message, $m)) {
				$message = $m[1];
			}

			throw new SshException($message);
		};

		set_error_handler($errorHandler);

		try {
			return call_user_func_array($command, $args);
		} finally {
			restore_error_handler();
		}
	}

}
