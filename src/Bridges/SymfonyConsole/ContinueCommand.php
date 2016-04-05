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


class ContinueCommand extends BaseCommand
{
	protected function configure()
	{
		$this->setName('migrations:continue');
		$this->setDescription('Updates database schema by running all new migrations');
		$this->setHelp("If table 'migrations' does not exist in current database, it is created automatically.");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_CONTINUE, $this->config);
	}

}
