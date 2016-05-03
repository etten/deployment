<?php

/**
 * This file is part of etten/deployment.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Tests\Unit;

use Etten\Deployment\FileCollector;
use org\bovigo\vfs;

class FileCollectorTest extends \PHPUnit_Framework_TestCase
{

	public function testCollect()
	{
		$structure = [
			'.git' => [],
			'app' => [
				'config' => [
					'config.neon' => '# file',
					'config.local.neon' => '# file',
					'extensions.neon' => '# file',
				],
				'models' => [],
			],
			'dev-local' => [
				'mindmap.pdf',
			],
			'temp' => [
				'cache' => [
					'cache' => '# file',
				],
				'sessions' => [
					'cache' => '# file',
				],
				'.htaccess' => '# file',
				'.journal' => '# file',
			],
			'src' => [
				'Etten' => [
					'Deployment' => [
						'Deployment.php' => '# file',
					],
				],
			],
			'.gitignore' => '# file',
			'.htaccess' => '# file',
		];

		$expected = [
			'/app/' => TRUE,
			'/app/config/' => TRUE,
			'/app/config/config.neon' => 'd45640831d1b9eb5c966a1345538a587',
			'/app/config/extensions.neon' => 'd45640831d1b9eb5c966a1345538a587',
			'/app/models/' => TRUE,
			'/temp/' => TRUE,
			'/temp/cache/' => TRUE,
			'/temp/sessions/' => TRUE,
			'/temp/.htaccess' => 'd45640831d1b9eb5c966a1345538a587',
			'/src/' => TRUE,
			'/src/Etten/' => TRUE,
			'/src/Etten/Deployment/' => TRUE,
			'/src/Etten/Deployment/Deployment.php' => 'd45640831d1b9eb5c966a1345538a587',
			'/.htaccess' => 'd45640831d1b9eb5c966a1345538a587',
		];

		$directory = vfs\vfsStream::setup('root', NULL, $structure);

		$collector = new FileCollector([
			'path' => $directory->url(),
			'ignore' => [
				'/dev-local',
				'/temp/*',
				'!/temp/.htaccess',
				'!/temp/sessions',
				'/temp/sessions/*',
				'!/temp/cache',
				'/temp/cache/*',
			],
			'force' => [],
		]);
		$this->assertSame($expected, $collector->collect());
	}

}
