<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment\Server;

use Etten\Deployment\TempFile;

class FtpServerCore implements Server
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

	/** @var int */
	private $maxRetries = 5;

	public function __construct(array $config)
	{
		$this->config = array_merge($this->config, $config);
	}

	public function exists(string $remotePath):bool
	{
		if ($this->isDirectory($remotePath)) {
			return $this->isDirectoryExists($remotePath);
		} else {
			return $this->isFileExists($remotePath);
		}
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
		try {
			if ($this->isDirectory($originalPath)) {
				$this->renameDirectory($originalPath, $newPath);
			} else {
				$this->renameFile($originalPath, $newPath);
			}
		} catch (FtpException $e) {
			if ($this->listDirectory($originalPath)) {
				throw $e;
			}
		}
	}

	public function remove(string $remotePath)
	{
		try {
			if ($this->isDirectory($remotePath)) {
				$this->removeDirectory($remotePath);
			} else {
				$this->removeFile($remotePath);
			}
		} catch (FtpException $e) {
			if ($this->listDirectory($remotePath)) {
				throw $e;
			}
		}
	}

	private function listDirectory(string $path):array
	{
		$list = $this->ftp('nlist', [$path]);

		// ftp_nlist may return false.
		if (!$list) {
			return [];
		}

		$filtered = array_filter($list, function (string $s) {
			return $s !== '.' && $s !== '..';
		});

		$mapped = array_map(function (string $s) {
			// Make path relative when is not (target server dependent?)
			$lastSlashPosition = strrpos($s, '/');
			if ($lastSlashPosition !== FALSE) {
				$s = substr($s, $lastSlashPosition + 1);
			}

			return $s;
		}, $filtered);

		return $mapped;
	}

	private function renameFile(string $originalPath, string $newPath)
	{
		// directory must be created before file
		$parts = explode('/', $newPath);
		array_pop($parts); // strip file name
		$directory = rtrim(implode('/', $parts), '/') . '/';

		if (!$this->isDirectoryExists($directory)) {
			$this->writeDirectory($directory);
		}

		// directory exists, rename the file
		$this->ftp('rename', [$originalPath, $newPath]);
	}

	private function renameDirectory(string $originalPath, string $newPath)
	{
		// Create a new directory
		$this->writeDirectory($newPath);

		// Walk directory contents
		$list = $this->listDirectory($originalPath);

		foreach ($list as $item) {
			// Build full file path
			$originalItemPath = rtrim($originalPath, '/') . '/' . $item;
			$newItemPath = rtrim($newPath, '/') . '/' . $item;

			// If is a directory, add directory separator to the end
			if ($this->isDirectoryExists($originalItemPath)) {
				$originalItemPath .= '/';
				$newItemPath .= '/';
			}

			// Rename current item
			$this->rename($originalItemPath, $newItemPath);
		}

		// All directory contents should be renamed, delete it
		$this->removeDirectory($originalPath);
	}

	private function removeFile(string $path)
	{
		$this->ftp('delete', [$path]);
	}

	private function removeDirectory(string $path)
	{
		$list = $this->listDirectory($path);

		foreach ($list as $item) {
			// Build full file path
			$item = rtrim($path, '/') . '/' . $item;

			// If is a directory, add directory separator to the end
			if ($this->isDirectoryExists($item)) {
				$item .= '/';
			}

			// Remove current item
			$this->remove($item);
		}

		// Directory should be empty now, delete it
		$this->ftp('rmdir', [$path]);
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

		if (!extension_loaded('ftp')) {
			throw new FtpException('PHP extension FTP is not loaded.');
		}

		if (!$this->config['host']) {
			throw new FtpException('HOST is not set.');
		}

		$this->connection = $this->protect(
			$this->config['secured'] ? 'ftp_ssl_connect' : 'ftp_connect',
			[$this->config['host'], $this->config['port']]
		);

		$this->ftp('login', [$this->config['user'], $this->config['password']]);
		$this->ftp('pasv', [$this->config['passive']]);
	}

	private function disconnect()
	{
		ftp_close($this->connection);
		$this->connection = NULL;
	}

	/**
	 * @param string $command
	 * @param array $args
	 * @return mixed
	 * @throws FtpException
	 */
	private function protect(string $command, array $args = [])
	{
		$errorHandler = function ($severity, $message) {
			if (preg_match('~^\w+\(\):\s*(.+)~', $message, $m)) {
				$message = $m[1];
			}

			throw new FtpException($message);
		};

		set_error_handler($errorHandler);

		try {
			return call_user_func_array($command, $args);
		} finally {
			restore_error_handler();
		}
	}

	private function isDirectory(string $path):bool
	{
		return substr($path, -1) === '/';
	}

	private function isDirectoryExists(string $path):bool
	{
		$currentDir = $this->ftp('pwd');

		$exists = $this->runRetry(function () use ($path) {
			try {
				return $this->ftp('chdir', [$path]);
			} catch (FtpException $e) {
				// Given path is not a directory...
				if (strpos($e->getMessage(), 'Not a directory') !== FALSE) {
					return FALSE;
				}

				// Directory is not exists...
				if (strpos($e->getMessage(), 'No such file or directory') !== FALSE) {
					return FALSE;
				}

				throw $e;
			}
		});

		$this->runRetry(function () use ($currentDir) {
			$this->ftp('chdir', [$currentDir ?: '/']);
		});

		return $exists;
	}

	private function isFileExists(string $path):bool
	{
		$tempFilePath = TempFile::create();

		try {
			$this->read($path, $tempFilePath);
			return TRUE;
		} catch (FtpException $e) {
			return FALSE;
		}
	}

	private function writeDirectory(string $remotePath)
	{
		if ($this->isDirectoryExists($remotePath)) {
			return;
		}

		$parts = explode('/', $remotePath);

		$path = '';
		while (!empty($parts)) {
			$path .= array_shift($parts);

			if ($path) {
				$this->runRetry(function () use ($path) {
					try {
						$this->ftp('mkdir', [$path]);
					} catch (FtpException $e) {
						// Ignore error when directory already exists
						if (strpos($e->getMessage(), 'File exists') === FALSE) {
							throw $e;
						}
					}
				});
			}

			$path .= '/';
		}
	}

	private function writeFile(string $remotePath, string $localPath)
	{
		$parts = explode('/', $remotePath);
		$this->writeDirectory(implode('/', array_slice($parts, 0, count($parts) - 1)));

		$this->runRetry(
			function () use ($remotePath, $localPath) {
				$this->ftp('put', [$remotePath, $localPath, FTP_BINARY]);
			}
		);
	}

	private function runRetry(\Closure $run, $attempt = 1)
	{
		try {
			return $run();

		} catch (FtpException $e) {
			if ($attempt >= $this->maxRetries) {
				throw $e;
			}

			$this->disconnect();
			$this->connect();

			return $this->runRetry($run, $attempt + 1);

		}
	}

}
