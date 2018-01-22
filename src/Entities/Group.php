<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Entities;

use Nextras\Migrations\IDiffGenerator;


/**
 * Group of migrations. Forms DAG with other groups.
 */
class Group
{
	/** @var string */
	public $name;

	/** @var bool */
	public $enabled = true;

	/** @var string absolute path do directory */
	public $directory;

	/** @var string[] */
	public $dependencies = [];

	/** @var IDiffGenerator|NULL */
	public $generator;


	/**
	 * @param null|string $name
	 * @param null|string $directory
	 */
	public function __construct($name = null, $directory = null)
	{
		$this->name = $name;
		$this->directory = $directory;
	}


	/**
	 * @param string $name
	 * @return static
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}


	/**
	 * @param bool $enabled
	 * @return static
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
		return $this;
	}


	/**
	 * @param string $directory
	 * @return static
	 */
	public function setDirectory($directory)
	{
		$this->directory = $directory;
		return $this;
	}


	/**
	 * @param string[] $dependencies
	 * @return static
	 */
	public function setDependencies(array $dependencies)
	{
		$this->dependencies = $dependencies;
		return $this;
	}


	/**
	 * @param IDiffGenerator|NULL $generator
	 * @return static
	 */
	public function setGenerator(IDiffGenerator $generator = NULL)
	{
		$this->generator = $generator;
		return $this;
	}
}
