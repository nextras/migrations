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
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;


abstract class BaseCommand extends Command
{
	/** @var IDriver */
	protected $driver;

	/** @var IConfiguration */
	protected $config;

	/** @var IPrinter */
	protected $printer;


	/**
	 * @param  IDriver        $driver
	 * @param  IConfiguration $config
	 * @param  IPrinter|NULL  $printer
	 */
	public function __construct(IDriver $driver, IConfiguration $config, IPrinter $printer = NULL)
	{
		$this->driver = $driver;
		$this->config = $config;
		$this->printer = $printer ?: new Console();
		parent::__construct();
	}


	/**
	 * @param  string         $mode Runner::MODE_*
	 * @param  IConfiguration $config
	 * @return int
	 */
	protected function runMigrations($mode, $config)
	{
		$runner = new Runner($this->driver, $this->printer);
		$runner->run($mode, $config);

		return 0;
	}

}
