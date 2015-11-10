<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Engine;

use DateTime;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\Exception;
use Nextras\Migrations\ExecutionException;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\LogicException;


class Runner
{
	/** @const modes */
	const MODE_CONTINUE = 'continue';
	const MODE_CONTINUE_FULL_ROLLBACK = 'continue-full-rollback';
	const MODE_RESET = 'reset';
	const MODE_INIT = 'init';

	/** @var IPrinter */
	private $printer;

	/** @var array (extension => IExtensionHandler) */
	private $extensionsHandlers = array();

	/** @var Group[] */
	private $groups = array();

	/** @var IDriver */
	private $driver;

	/** @var Finder */
	private $finder;

	/** @var OrderResolver */
	private $orderResolver;


	public function __construct(IDriver $driver, IPrinter $printer)
	{
		$this->driver = $driver;
		$this->printer = $printer;
		$this->finder = new Finder;
		$this->orderResolver = new OrderResolver;
	}


	public function addGroup(Group $group)
	{
		$this->groups[] = $group;
		return $this;
	}


	/**
	 * @param  string            $extension
	 * @param  IExtensionHandler $handler
	 * @return self
	 */
	public function addExtensionHandler($extension, IExtensionHandler $handler)
	{
		if (isset($this->extensionsHandlers[$extension])) {
			throw new LogicException("Extension '$extension' has already been defined.");
		}

		$this->extensionsHandlers[$extension] = $handler;
		return $this;
	}


	/**
	 * @param  string $mode self::MODE_CONTINUE|self::MODE_CONTINUE_FULL_ROLLBACK|self::MODE_RESET|self::MODE_INIT
	 * @return void
	 */
	public function run($mode = self::MODE_CONTINUE)
	{
		if ($mode === self::MODE_INIT) {
			$this->printer->printSource($this->driver->getInitTableSource() . "\n");
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$files = $this->orderResolver->resolve(array(), $this->groups, $files, self::MODE_RESET);
			$this->printer->printSource($this->driver->getInitMigrationsSource($files));
			return;
		}

		try {

			$this->driver->setupConnection();
			$this->driver->lock();

			if ($mode === self::MODE_RESET) {
				$this->driver->emptyDatabase();
				$this->printer->printReset();
			}

			$this->driver->beginTransaction();
			try {
				$this->driver->createTable();
				$migrations = $this->driver->getAllMigrations();
				$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
				$toExecute = $this->orderResolver->resolve($migrations, $this->groups, $files, $mode);
				$this->printer->printToExecute($toExecute);

				foreach ($toExecute as $file) {
					$queriesCount = $this->execute($file);
					$this->printer->printExecute($file, $queriesCount);
				}
				$this->driver->commitTransaction();

			} catch (\Exception $e) {
				if ($mode === self::MODE_CONTINUE_FULL_ROLLBACK) {
					// rollback all migrations executed in this run
					$this->driver->rollbackTransaction();

				} else if ($mode === self::MODE_CONTINUE) {
					// commit migrations not including the failing one
					$this->driver->commitTransaction();
				}
				throw $e;
			}

			$this->driver->unlock();
			$this->printer->printDone();

		} catch (Exception $e) {
			$this->driver->unlock();
			$this->printer->printError($e);
		}
	}


	/**
	 * @param  string $name
	 * @return IExtensionHandler
	 */
	public function getExtension($name)
	{
		if (!isset($this->extensionsHandlers[$name])) {
			throw new LogicException("Extension '$name' not found.");
		}
		return $this->extensionsHandlers[$name];
	}


	/**
	 * @param  File $file
	 * @return int  number of executed queries
	 */
	protected function execute(File $file)
	{
		$this->driver->beginTransaction();

		$migration = new Migration;
		$migration->group = $file->group->name;
		$migration->filename = $file->name;
		$migration->checksum = $file->checksum;
		$migration->executedAt = new DateTime('now');

		$this->driver->insertMigration($migration);

		try {
			$queriesCount = $this->getExtension($file->extension)->execute($file);
		} catch (\Exception $e) {
			$this->driver->rollbackTransaction();
			throw new ExecutionException(sprintf('Executing migration "%s" has failed.', $file->path), NULL, $e);
		}

		$this->driver->markMigrationAsReady($migration);
		$this->driver->commitTransaction();

		return $queriesCount;
	}

}
