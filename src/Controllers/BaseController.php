<?php declare(strict_types = 1);

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
use Nextras\Migrations\IPrinter;


abstract class BaseController
{
	/** @var Engine\Runner */
	protected $runner;

	/** @var string */
	protected $mode;

	/** @var array<string, Group> (name => Group) */
	protected $groups;


	public function __construct(IDriver $driver)
	{
		$printer = $this->createPrinter();
		$this->runner = new Engine\Runner($driver, $printer);
		$this->mode = Engine\Runner::MODE_CONTINUE;
		$this->groups = [];
	}


	abstract public function run(): void;


    /**
     * @param  list<string>  $dependencies
     */
	public function addGroup(string $name, string $dir, array $dependencies = []): self
	{
		$group = new Group;
		$group->name = $name;
		$group->directory = $dir;
		$group->dependencies = $dependencies;
		$group->enabled = false;

		$this->groups[$name] = $group;
		return $this;
	}


	public function addExtension(string $extension, IExtensionHandler $handler): self
	{
		$this->runner->addExtensionHandler($extension, $handler);
		return $this;
	}


    /**
     * @return list<string>
     */
	protected function registerGroups(): array
	{
		$enabled = [];

		foreach ($this->groups as $group) {
			$this->runner->addGroup($group);

			if ($group->enabled) {
				$enabled[] = $group->name;
			}
		}

		return $enabled;
	}


	protected function setupPhp(): void
	{
		@set_time_limit(0);
		@ini_set('memory_limit', '1G');
	}


	abstract protected function createPrinter(): IPrinter;
}
