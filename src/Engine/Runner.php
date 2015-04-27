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

	/** @var string */
	private $tempDir;


	public function __construct(IDriver $driver, IPrinter $printer, $tempDir = NULL)
	{
		$this->driver = $driver;
		$this->printer = $printer;
		$this->finder = new Finder;
		$this->orderResolver = new OrderResolver;
		$this->tempDir = $tempDir;
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
	 * @param  string $mode self::MODE_CONTINUE|self::MODE_RESET|self::MODE_INIT
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

			$this->printer->printIntro($mode);
			if ($mode === self::MODE_RESET) {
				$this->driver->emptyDatabase();
			}

			$this->driver->createTable();
			$migrations = $this->driver->getAllMigrations();
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$toExecute = $this->orderResolver->resolve($migrations, $this->groups, $files, $mode);
			$this->printer->printToExecute($toExecute);

			$this->execute($toExecute, $this->tempDir && count($migrations) === 0);

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
	 * @param  File[] $files
	 * @param  bool   $useSnapshots
	 * @return void
	 */
	protected function execute($files, $useSnapshots)
	{
		$createSnapshot = FALSE;
		$startIndex = 0;

		if ($useSnapshots) {
			if (!is_dir($this->tempDir)) {
				mkdir($this->tempDir);
			}

			$keys = [];
			$prevKey = '';
			foreach ($files as $i => $file) {
				$keys[$i] = $prevKey = md5($file->checksum . $prevKey);
			}

			foreach (array_reverse($keys, TRUE) as $i => $key) {
				$path = sprintf('%s/%03d-%s.sql', $this->tempDir, $i + 1, $key);
				if (is_file($path)) {
					$this->executeFile($this->createSnapshotFile($path));
					$startIndex = $i + 1;
					break;
				}
			}
		}

		for ($i = $startIndex; $i < count($files); $i++) {
			$this->executeFile($files[$i], $this->createMigration($files[$i]));
			$createSnapshot = $useSnapshots;
		}

		if ($createSnapshot) {
			$path = sprintf('%s/%03d-%s.sql', $this->tempDir, count($files), $prevKey);
			$this->driver->saveFile($path);
		}
	}


	/**
	 * @param  File           $file
	 * @param  Migration|NULL $migration
	 * @return void
	 */
	protected function executeFile(File $file, Migration $migration = NULL)
	{
		try {
			$this->driver->beginTransaction();

			if ($migration) $this->driver->insertMigration($migration);
			$time = -microtime(TRUE);
			$queriesCount = $this->getExtension($file->extension)->execute($file);
			$time += microtime(TRUE);
			$this->printer->printExecute($file, $queriesCount, $time);
			if ($migration) $this->driver->markMigrationAsReady($migration);

			$this->driver->commitTransaction();

		} catch (\Exception $e) {
			$this->driver->rollbackTransaction();
			throw new ExecutionException(sprintf('Executing migration "%s" has failed.', $file->path), NULL, $e);
		}
	}


	/**
	 * @param  string $path
	 * @return File
	 */
	protected function createSnapshotFile($path)
	{
		$file = new File();
		$file->extension = 'sql';
		$file->name = basename($path);
		$file->path = $path;
		$file->group = new Group();
		$file->group->name = 'snapshots';
		$file->group->enabled = TRUE;
		$file->group->directory = $this->tempDir;
		$file->group->dependencies = [];
		return $file;
	}


	/**
	 * @param  File $file
	 * @return Migration
	 */
	protected function createMigration(File $file)
	{
		$migration = new Migration;
		$migration->group = $file->group->name;
		$migration->filename = $file->name;
		$migration->checksum = $file->checksum;
		$migration->executedAt = new DateTime('now');
		return $migration;
	}

}
