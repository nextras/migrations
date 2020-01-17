<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;


abstract class BaseCommand extends Command
{
	/** @var IDriver */
	protected $driver;

	/** @var IConfiguration */
	protected $config;


	/**
	 * @param  IDriver        $driver
	 * @param  IConfiguration $config
	 */
	public function __construct(IDriver $driver, IConfiguration $config)
	{
		$this->driver = $driver;
		$this->config = $config;
		parent::__construct();
	}


	/**
	 * @param  string         $mode Runner::MODE_*
	 * @param  IConfiguration $config
	 * @return int
	 */
	protected function runMigrations($mode, $config)
	{
		$printer = new Console();
		$runner = new Runner($this->driver, $printer);
		$runner->run($mode, $config);

		return 0;
	}

}
