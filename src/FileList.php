<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class FileList
{

	public function write(string $file, array $files)
	{
		$content = json_encode($files);
		return file_put_contents($file, gzdeflate($content, 9));
	}

	public function read(string $file) :array
	{
		$content = gzinflate(file_get_contents($file));
		return json_decode($content, TRUE);
	}

}
