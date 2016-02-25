<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

interface Server
{

	/**
	 * Checks if remote file or directory exists.
	 * Directory must end with slash (/) at the end of path.
	 * @param string $remotePath
	 * @return bool
	 */
	public function exists(string $remotePath):bool;

	/**
	 * Reads the remote file and saves it locally.
	 * @param string $remotePath
	 * @param string $localPath
	 * @return void
	 */
	public function read(string $remotePath, string $localPath);

	/**
	 * Writes remote file or directory (recursively).
	 * Directory must end with slash (/) at the end of path.
	 * @param string $remotePath
	 * @param string $localPath
	 * @return void
	 */
	public function write(string $remotePath, string $localPath);

	/**
	 * Renames remote file or folder.
	 * Directory must end with slash (/) at the end of path.
	 * @param string $originalPath
	 * @param string $newPath
	 * @return void
	 */
	public function rename(string $originalPath, string $newPath);

	/**
	 * Removes remote file or directory.
	 * Directory must end with slash (/) at the end of path.
	 * @param string $remotePath
	 * @return void
	 */
	public function remove(string $remotePath);

}
