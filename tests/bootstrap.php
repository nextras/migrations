<?php

namespace Nextras\Migrations\Tests;

use Tester;
use Nette;


require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

$configurator = new Nette\Configurator;
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../Nextras')
	->register();

return $configurator->createContainer();
