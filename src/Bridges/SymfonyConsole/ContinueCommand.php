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


class ContinueCommand extends BaseCommand
{
	/** @var string */
	protected static $defaultName = 'migrations:continue';


	protected function configure()
	{
		$this->setName(self::$defaultName);
		$this->setDescription('Updates database schema by running all new migrations');
		$this->setHelp("If table 'migrations' does not exist in current database, it is created automatically.");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		return $this->runMigrations(Runner::MODE_CONTINUE, $this->config);
	}

}
