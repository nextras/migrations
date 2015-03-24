<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Configurations;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Extensions\PhpHandler;
use Nextras\Migrations\Extensions\SqlHandler;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\Printers\Console;


/**
 * @author Jan Tvrdík
 */
class DefaultConfiguration implements IConfiguration
{
	/** @var IDriver */
	protected $driver;

	/** @var string */
	protected $dir;

	/** @var array */
	protected $phpParams;

	/** @var bool */
	protected $withDummy;


	/**
	 * @param  IDriver $driver
	 * @param  string  $dir
	 * @param  array   $phpParams (name => value)
	 * @param  bool    $withDummy
	 */
	public function __construct(IDriver $driver, $dir, $phpParams = [], $withDummy = TRUE)
	{
		$this->driver = $driver;
		$this->dir = $dir;
		$this->phpParams = $phpParams;
		$this->withDummy = $withDummy;
	}


	/**
	 * @return Group[]
	 */
	public function getGroups()
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
		$dummyData->enabled = $this->withDummy;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $this->dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}


	/**
	 * @return array (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers()
	{
		return [
			'sql' => new SqlHandler($this->driver),
			'php' => new PhpHandler($this->phpParams),
		];
	}

}
