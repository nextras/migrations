<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Extensions;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;


abstract class BaseCommand extends Command
{
	/** @var IDriver */
	protected $driver;

	/** @var IConfiguration */
	protected $devConfig;

	/** @var IConfiguration */
	protected $prodConfig;


	/**
	 * @param  IDriver $driver
	 * @param  string  $dir
	 * @param  array   $phpParams (name => value)
	 */
	public function __construct(IDriver $driver, IConfiguration $devConfig, IConfiguration $prodConfig)
	{
		parent::__construct();
		$this->driver = $driver;
		$this->devConfig = $devConfig;
		$this->prodConfig = $prodConfig;
	}


	/**
	 * @param  string         $mode Runner::MODE_*
	 * @param  IConfiguration $config
	 * @return void
	 */
	protected function runMigrations($mode, $config)
	{
		$printer = new Console();
		$runner = new Runner($this->driver, $printer);
		$runner->run($mode, $config);
	}

}
