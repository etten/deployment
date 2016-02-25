<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

use Etten\Deployment\Exceptions\Exception;
use Etten\Deployment\Exceptions\FtpException;

class FtpServer implements Server
{

	/** @var array */
	private $config = [
		'host' => NULL,
		'port' => NULL,
		'user' => NULL,
		'password' => NULL,
		'secured' => TRUE,
		'passive' => TRUE,
	];

	/** @var resource */
	private $connection;

	public function __construct(array $config)
	{
		if (!extension_loaded('ftp')) {
			throw new Exception('PHP extension FTP is not loaded.');
		}

		$this->config = array_merge($this->config, $config);
	}

	public function read(string $remotePath, string $localPath)
	{
		$this->ftp('get', [$localPath, $remotePath, FTP_BINARY]);
	}

	public function write(string $remotePath, string $localPath)
	{
		if ($this->isDirectory($remotePath)) {
			$this->writeDirectory($remotePath);
		} else {
			$this->writeFile($remotePath, $localPath);
		}
	}

	public function rename(string $originalPath, string $newPath)
	{
		$this->remove($newPath);
		$this->ftp('rename', [$originalPath, $newPath]);
	}

	public function remove(string $remotePath)
	{
		$command = $this->isDirectory($remotePath) ?
			'rmDir' :
			'delete';

		try {
			$this->ftp($command, [$remotePath]);
		} catch (FtpException $e) {
			if ($this->ftp('nlist', [$remotePath])) {
				throw $e;
			}
		}
	}

	/**
	 * @param string $command FTP command name
	 * @param array $args
	 * @return mixed
	 * @throws FtpException
	 */
	private function ftp(string $command, array $args = [])
	{
		$this->connect();

		array_unshift($args, $this->connection);
		return $this->protect('ftp_' . $command, $args);
	}

	private function connect()
	{
		if ($this->connection) {
			return;
		}

		$this->connection = $this->protect(
			$this->config['secured'] ? 'ftp_ssl_connect' : 'ftp_connect',
			[$this->config['host'], $this->config['port']]
		);

		$this->ftp('login', [$this->config['user'], $this->config['password']]);
		$this->ftp('pasv', [$this->config['passive']]);
	}

	/**
	 * Method extracted from dg/ftp-deployment.
	 * @see https://github.com/dg/ftp-deployment/blob/master/src/Deployment/FtpServer.php#L284
	 * @param string $command
	 * @param array $args
	 * @return mixed
	 * @throws FtpException
	 */
	private function protect(string $command, array $args = [])
	{
		set_error_handler(function ($severity, $message) {
			restore_error_handler();
			if (preg_match('~^\w+\(\):\s*(.+)~', $message, $m)) {
				$message = $m[1];
			}

			throw new FtpException($message);
		});

		$res = call_user_func_array($command, $args);
		restore_error_handler();
		return $res;
	}

	private function isDirectory(string $path):bool
	{
		return substr($path, -1) === '/';
	}

	private function writeDirectory(string $remotePath)
	{
		$parts = explode('/', $remotePath);

		$path = '';
		while (!empty($parts)) {
			$path .= array_shift($parts);

			try {
				if ($path !== '') {
					$this->ftp('mkdir', [$path]);
				}
			} catch (FtpException $e) {
				// Ignore error when directory already exists
				if (strpos($e->getMessage(), 'File exists') === FALSE) {
					throw $e;
				}
			}

			$path .= '/';
		}
	}

	private function writeFile(string $remotePath, string $localPath)
	{
		$parts = explode('/', $remotePath);
		$this->writeDirectory(implode('/', array_slice($parts, 0, count($parts) - 1)));

		$blocks = 0;
		do {
			$ret = $blocks === 0
				? $this->ftp('nb_put', [$remotePath, $localPath, FTP_BINARY])
				: $this->ftp('nb_continue');

			$blocks++;

		} while ($ret === FTP_MOREDATA);
	}

}
