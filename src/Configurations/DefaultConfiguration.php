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
use Nextras\Migrations\IDiffGenerator;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;


/**
 * @author Jan Tvrdík
 * @deprecated
 */
class DefaultConfiguration implements IConfiguration
{
	/** @var string */
	protected $dir;

	/** @var IDriver */
	protected $driver;

	/** @var bool */
	protected $withDummyData;

	/** @var array */
	protected $phpParams;

	/** @var Group[] */
	protected $groups;

	/** @var IExtensionHandler[] */
	protected $handlers;

	/** @var IDiffGenerator|NULL */
	protected $structureDiffGenerator;

	/** @var IDiffGenerator|NULL */
	protected $dummyDataDiffGenerator;


	/**
	 * @param  string  $dir
	 * @param  IDriver $driver
	 * @param  bool    $withDummyData
	 * @param  array   $phpParams
	 */
	public function __construct($dir, IDriver $driver, $withDummyData = TRUE, array $phpParams = [])
	{
		$this->dir = $dir;
		$this->driver = $driver;
		$this->withDummyData = $withDummyData;
		$this->phpParams = $phpParams;
	}


	/**
	 * @return Group[]
	 */
	public function getGroups()
	{
		if ($this->groups === NULL) {
			$structures = new Group();
			$structures->enabled = TRUE;
			$structures->name = 'structures';
			$structures->directory = $this->dir . '/structures';
			$structures->dependencies = [];
			$structures->generator = $this->structureDiffGenerator;

			$basicData = new Group();
			$basicData->enabled = TRUE;
			$basicData->name = 'basic-data';
			$basicData->directory = $this->dir . '/basic-data';
			$basicData->dependencies = ['structures'];

			$dummyData = new Group();
			$dummyData->enabled = $this->withDummyData;
			$dummyData->name = 'dummy-data';
			$dummyData->directory = $this->dir . '/dummy-data';
			$dummyData->dependencies = ['structures', 'basic-data'];
			$dummyData->generator = $this->dummyDataDiffGenerator;

			$this->groups = [$structures, $basicData, $dummyData];
		}

		return $this->groups;
	}


	/**
	 * @return array|IExtensionHandler[] (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers()
	{
		if ($this->handlers === NULL) {
			$this->handlers = [
				'sql' => new SqlHandler($this->driver),
				'php' => new PhpHandler($this->phpParams),
			];
		}

		return $this->handlers;
	}


	/**
	 * @param  IDiffGenerator|NULL $generator
	 * @return void
	 */
	public function setStructureDiffGenerator(IDiffGenerator $generator = NULL)
	{
		$this->structureDiffGenerator = $generator;
	}


	/**
	 * @param  IDiffGenerator|NULL $generator
	 * @return void
	 */
	public function setDummyDataDiffGenerator(IDiffGenerator $generator = NULL)
	{
		$this->dummyDataDiffGenerator = $generator;
	}

}
