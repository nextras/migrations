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


class StatusCommand extends BaseCommand
{
	/** @var string */
	protected static $defaultName = 'migrations:status';


	protected function configure()
	{
		$this->setName(self::$defaultName);
		$this->setDescription('Show lists of completed or waiting migrations');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_STATUS, $this->config);
	}

}
