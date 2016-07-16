<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Controllers;

use Nextras\Migrations\Engine;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;


abstract class BaseController
{
	/** @var Engine\Runner */
	protected $runner;

	/** @var string */
	protected $mode;

	/** @var array (name => Group) */
	protected $groups;


	public function __construct(IDriver $driver)
	{
		$printer = $this->createPrinter();
		$this->runner = new Engine\Runner($driver, $printer);
		$this->mode = Engine\Runner::MODE_CONTINUE;
		$this->groups = array();
	}


	abstract public function run();


	public function addGroup($name, $dir, array $dependencies = array())
	{
		$group = new Group;
		$group->name = $name;
		$group->directory = $dir;
		$group->dependencies = $dependencies;
		$group->enabled = FALSE;

		$this->groups[$name] = $group;
		return $this;
	}


	public function addExtension($extension, IExtensionHandler $handler)
	{
		$this->runner->addExtensionHandler($extension, $handler);
		return $this;
	}


	protected function registerGroups()
	{
		$enabled = array();
		foreach ($this->groups as $group) {
			$this->runner->addGroup($group);
			if ($group->enabled) {
				$enabled[] = $group->name;
			}
		}
		return $enabled;
	}


	protected function setupPhp()
	{
		@set_time_limit(0);
		@ini_set('memory_limit', '1G');
	}


	abstract protected function createPrinter();

}
