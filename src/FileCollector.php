<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class FileCollector implements Collector
{

	/** @var string */
	private $basePath;

	/** @var string[] */
	private $ignoreMasks = [
		'*.local.neon',
		'.git*',
		'Thumbs.db',
		'.DS_Store',
	];

	/** @var Logger */
	private $logger;

	public function __construct(array $config)
	{
		$this->basePath = $config['path'];
		$this->ignoreMasks = array_merge($this->ignoreMasks, $config['ignore']);
		$this->logger = new VoidLogger();
	}

	/**
	 * @return string Full local base path.
	 */
	public function basePath():string
	{
		return $this->basePath;
	}

	/**
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;
		return $this;
	}

	/**
	 * @return array [relativePath => hash]
	 */
	public function collect():array
	{
		return $this->collectRecursively('');
	}

	private function collectRecursively($directory = ''):array
	{
		$this->logger->log(sprintf('Checking %s', $directory ?: '/'));

		$list = [];
		$iterator = dir($this->basePath . $directory);

		while (($entry = $iterator->read())) {
			$shortPath = "$directory/$entry";
			$fullPath = $this->basePath . $shortPath;

			if ($entry === '.' || $entry === '..') {
				continue;

			} elseif ($this->isIgnored($shortPath)) {
				continue;

			} elseif (is_dir($fullPath)) {
				$list[$shortPath . '/'] = TRUE;
				$list += $this->collectRecursively($shortPath);

			} elseif (is_file($fullPath)) {
				$list[$shortPath] = $this->hashFile($fullPath);
			}
		}

		$iterator->close();

		return $list;
	}

	/**
	 * Whole method is extracted from dg/ftp-deployment.
	 * @see https://github.com/dg/ftp-deployment/blob/master/src/Deployment/Deployer.php#L419
	 * @param string $file
	 * @return string
	 */
	private function hashFile(string $file):string
	{
		if (filesize($file) > 5e6) {
			return md5_file($file);

		} else {
			$s = file_get_contents($file);
			if (preg_match('#^[\x09\x0A\x0D\x20-\x7E\x80-\xFF]*+\z#', $s)) {
				$s = str_replace("\r\n", "\n", $s);
			}

			return md5($s);
		}
	}

	/**
	 * Extracted from dg/ftp-deployment and refactored.
	 * @see https://github.com/dg/ftp-deployment/blob/master/src/Deployment/Deployer.php#L438
	 * @param string $name
	 * @return bool
	 */
	private function isIgnored(string $name):bool
	{
		$isIgnored = FALSE;
		$path = explode('/', ltrim($name, '/'));

		foreach ($this->ignoreMasks as $mask) {
			$mask = $this->normalizePath($mask);

			$isNegation = substr($mask, 0, 1) === '!';
			if ($isNegation) {
				$mask = substr($mask, 1);
			}

			if (strpos($mask, '/') === FALSE) { // no slash means base name
				if (fnmatch($mask, end($path), FNM_CASEFOLD)) {
					$isIgnored = !$isNegation;
				}

			} else {
				$parts = explode('/', ltrim($mask, '/'));
				if (fnmatch(
					implode('/', $isNegation ? array_slice($parts, 0, count($path)) : $parts),
					implode('/', array_slice($path, 0, count($parts))),
					FNM_CASEFOLD | FNM_PATHNAME
				)) {
					$isIgnored = !$isNegation;
				}
			}
		}

		return $isIgnored;
	}

	private function normalizePath(string $path):string
	{
		return strtr($path, '\\', '/');
	}

}
