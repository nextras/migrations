<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nette;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Extensions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ResetCommand extends BaseCommand
{
	protected function configure()
	{
		$this->setName('migrations:reset');
		$this->setDescription('Drops current database and recreates it from scratch');
		$this->setHelp("Drops current database and runs all migrations");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_RESET, $this->config);
	}

}
