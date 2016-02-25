<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Deployment;

class TempFile
{

	/** @var resource[] */
	private static $streams = [];

	public static function create()
	{
		self::$streams[] = $temp = tmpfile();
		return stream_get_meta_data($temp)['uri'];
	}

}
