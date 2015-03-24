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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ResetCommand extends BaseCommand
{
	protected function configure()
	{
		$this->setName('migrations:reset');
		$this->setDescription('Drops current database and recreates it from scratch');
		$this->setHelp("Drops current database and runs all migrations");
		$this->addOption('production', NULL, InputOption::VALUE_NONE, 'Will not import dummy data');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$config = $input->getOption('production') ? $this->prodConfig : $this->devConfig;
		$this->runMigrations(Runner::MODE_RESET, $config);
	}

}
