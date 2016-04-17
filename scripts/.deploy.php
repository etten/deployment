<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class Deploy
{

	/** @var string */
	private $root;

	/** @var string */
	private $temp = '/.deploy';

	/** @var string */
	private $deleted = '/.deleted';

	public function __construct(string $root)
	{
		$this->root = $root;
	}

	public function moveFiles()
	{
		$temp = $this->root . $this->temp;
		if (is_dir($temp)) {
			$this->rename($temp, $this->root);
		}
	}

	public function deleteFiles()
	{
		$deleted = $this->root . $this->deleted;
		if (is_file($deleted)) {
			$files = $this->readDeletedFiles($deleted);
			foreach ($files as $file) {
				$this->delete($this->root . '/' . $file);
			}

			$this->delete($deleted);
		}

		$temp = $this->root . $this->temp;
		if (is_dir($temp)) {
			$this->delete($temp);
		}
	}

	private function readDeletedFiles(string $file):array
	{
		$content = gzinflate(file_get_contents($file));
		$list = json_decode($content, TRUE);
		return array_keys($list);
	}

	private function readFiles(string $path):array
	{
		$files = scandir($path);
		$files = array_diff($files, ['.', '..']);
		return $files;
	}

	private function rename(string $from, string $to)
	{
		if ($this->isCli()) {
			$this->renameSystem($from, $to);
		} else {
			$this->renamePhp($from, $to);
		}
	}

	private function renameSystem(string $from, string $to)
	{
		if (is_dir($from)) {
			passthru('cp -a ' . escapeshellarg($from . '/.') . ' ' . escapeshellarg($to . '/'));
			$this->deleteSystem($from);
		} else {
			passthru('mv -f ' . escapeshellarg($from) . ' ' . escapeshellarg($to));
		}
	}

	private function renamePhp(string $from, string $to)
	{
		if (is_dir($from)) {
			foreach ($this->readFiles($from) as $file) {
				$this->renamePhp($from . '/' . $file, $to . '/' . $file);
			}

			if (is_dir($to)) {
				rmdir($from);
			} else {
				rename($from, $to);
			}

		} else {
			rename($from, $to);
		}
	}

	private function delete(string $path)
	{
		if ($this->isCli()) {
			$this->deleteSystem($path);
		} else {
			$this->deletePhp($path);
		}
	}

	private function deleteSystem(string $path)
	{
		passthru('rm -rf ' . escapeshellarg($path));
	}

	private function deletePhp(string $path)
	{
		if (is_dir($path)) {
			foreach ($this->readFiles($path) as $file) {
				$this->deletePhp($path . '/' . $file);
			}
			rmdir($path);
		} else {
			unlink($path);
		}
	}

	private function isCli():bool
	{
		return php_sapi_name() === 'cli';
	}

}

$deploy = new Deploy(__DIR__);
$deploy->moveFiles();
$deploy->deleteFiles();
