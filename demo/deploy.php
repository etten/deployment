<?php

use Etten\Deployment;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Symfony\Component\Console\Application();
$app->add(new Deployment\SymfonyConsole\DeploymentCommand());

return $app->run();
