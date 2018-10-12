<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nextras\Migrations\Engine\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CheckCommand extends BaseCommand
{
	/** @var string */
	protected static $defaultName = 'migrations:check';


	protected function configure()
	{
		$this->setName(self::$defaultName);
		$this->setDescription('Check correct order of all migrations and check checksum for already executed.');
		$this->setHelp("If table 'migrations' does not exist in current database, it is created automatically.");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_CHECK, $this->config);
	}

}
