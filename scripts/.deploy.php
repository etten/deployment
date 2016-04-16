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
			$files = $this->readFiles($temp);
			foreach ($files as $file) {
				$this->rename($temp . '/' . $file, $this->root . '/' . $file);
			}
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
		if (is_dir($from)) {
			foreach ($this->readFiles($from) as $file) {
				$this->rename($from . '/' . $file, $to . '/' . $file);
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
		if (is_dir($path)) {
			foreach ($this->readFiles($path) as $file) {
				$this->delete($path . '/' . $file);
			}
			rmdir($path);
		} else {
			unlink($path);
		}
	}

}

$deploy = new Deploy(__DIR__);
$deploy->moveFiles();
$deploy->deleteFiles();
