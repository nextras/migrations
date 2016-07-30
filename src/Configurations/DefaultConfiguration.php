<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Configurations;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDiffGenerator;
use Nextras\Migrations\IExtensionHandler;


/**
 * @author Jan Tvrdík
 */
class DefaultConfiguration implements IConfiguration
{
	/** @var string */
	protected $dir;

	/** @var bool */
	protected $withDummyData;

	/** @var IExtensionHandler[] */
	protected $handlers;

	/** @var IDiffGenerator|NULL */
	protected $structureDiffGenerator;

	/** @var IDiffGenerator|NULL */
	protected $dummyDataDiffGenerator;


	/**
	 * @param  string              $dir
	 * @param  IExtensionHandler[] $handlers       (extension => IExtensionHandler)
	 * @param  bool                $withDummyData
	 */
	public function __construct($dir, array $handlers, $withDummyData = TRUE)
	{
		$this->dir = $dir;
		$this->handlers = $handlers;
		$this->withDummyData = $withDummyData;
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

		return [$structures, $basicData, $dummyData];
	}


	/**
	 * @return IExtensionHandler[] (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers()
	{
		return $this->handlers;
	}


	/**
	 * @param IDiffGenerator|NULL $generator
	 */
	public function setStructureDiffGenerator(IDiffGenerator $generator = NULL)
	{
		$this->structureDiffGenerator = $generator;
	}


	/**
	 * @param IDiffGenerator|NULL $generator
	 */
	public function setDummyDataDiffGenerator(IDiffGenerator $generator = NULL)
	{
		$this->dummyDataDiffGenerator = $generator;
	}

}
