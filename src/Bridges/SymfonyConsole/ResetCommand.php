<?php declare(strict_types = 1);

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


class ResetCommand extends BaseCommand
{
	/** @var string */
	protected static $defaultName = 'migrations:reset';

	/** @var string */
	protected static $defaultDescription = 'Drops current database and recreates it from scratch';


	protected function configure()
	{
		$this->setName(self::$defaultName);
		$this->setDescription(self::$defaultDescription);
		$this->setHelp("Drops current database and runs all migrations");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		return $this->runMigrations(Runner::MODE_RESET, $this->config);
	}
}
