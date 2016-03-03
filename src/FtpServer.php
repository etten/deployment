<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

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
			if ($this->ftp('nlist', [$originalPath])) {
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
			if ($this->ftp('nlist', [$remotePath])) {
				throw $e;
			}
		}
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
		$list = $this->ftp('nlist', [$originalPath]);

		foreach ($list as $item) {
			// Skip current and previous directory mark
			if (in_array($item, ['.', '..'])) {
				continue;
			}

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
		$list = $this->ftp('nlist', [$path]);

		foreach ($list as $item) {
			// Skip current and previous directory mark
			if (in_array($item, ['.', '..'])) {
				continue;
			}

			// Build full file path
			$itemPath = rtrim($path, '/') . '/' . $item;

			// If is a directory, add directory separator to the end
			if ($this->isDirectoryExists($itemPath)) {
				$itemPath .= '/';
			}

			// Remove current item
			$this->remove($itemPath);
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
			throw new Exception('PHP extension FTP is not loaded.');
		}

		$this->connection = $this->protect(
			$this->config['secured'] ? 'ftp_ssl_connect' : 'ftp_connect',
			[$this->config['host'], $this->config['port']]
		);

		$this->ftp('login', [$this->config['user'], $this->config['password']]);
		$this->ftp('pasv', [$this->config['passive']]);
	}

	/**
	 * @param string $command
	 * @param array $args
	 * @return mixed
	 * @throws FtpException
	 */
	private function protect(string $command, array $args = [])
	{
		$ftpExceptionErrorHandler = function ($severity, $message) {
			if (preg_match('~^\w+\(\):\s*(.+)~', $message, $m)) {
				$message = $m[1];
			}

			throw new FtpException($message);
		};

		set_error_handler($ftpExceptionErrorHandler);

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
		$exists = TRUE;
		$currentDir = $this->ftp('pwd');

		try {
			$this->ftp('chdir', [$path]);
		} catch (FtpException $e) {
			$exists = FALSE;
		}

		$this->ftp('chdir', [$currentDir ?: '/']);
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
