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
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;


abstract class BaseCommand extends Command
{
	/** @var IDriver */
	private $driver;

	/** @var string */
	private $dir;

	/** @var array */
	private $phpParams;


	/**
	 * @param  IDriver $driver
	 * @param  string  $dir
	 * @param  array   $phpParams (name => value)
	 */
	public function __construct(IDriver $driver, $dir, $phpParams = [])
	{
		parent::__construct();
		$this->driver = $driver;
		$this->dir = $dir;
		$this->phpParams = $phpParams;
	}


	/**
	 * @param  string $mode Runner::MODE_*
	 * @param  bool   $withDummy include dummy data?
	 * @return void
	 */
	protected function runMigrations($mode, $withDummy)
	{
		$printer = $this->getPrinter();
		$runner = new Runner($this->driver, $printer);

		foreach ($this->getGroups($withDummy) as $group) {
			$runner->addGroup($group);
		}

		foreach ($this->getExtensionHandlers() as $ext => $handler) {
			$runner->addExtensionHandler($ext, $handler);
		}

		$runner->run($mode);
	}


	/**
	 * @param  bool $withDummy
	 * @return Group[]
	 */
	protected function getGroups($withDummy)
	{
		$structures = new Group();
		$structures->enabled = TRUE;
		$structures->name = 'structures';
		$structures->directory = $this->dir . '/structures';
		$structures->dependencies = [];

		$basicData = new Group();
		$basicData->enabled = TRUE;
		$basicData->name = 'basic-data';
		$basicData->directory = $this->dir . '/basic-data';
		$basicData->dependencies = ['structures'];

		$dummyData = new Group();
		$dummyData->enabled = $withDummy;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $this->dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}


	/**
	 * @return array (extension => IExtensionHandler)
	 */
	protected function getExtensionHandlers()
	{
		return [
			'sql' => new Extensions\SqlHandler($this->driver),
			'php' => new Extensions\PhpHandler($this->phpParams),
		];
	}


	/**
	 * @return IPrinter
	 */
	protected function getPrinter()
	{
		return new Console();
	}

}
