<?php declare(strict_types = 1);

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
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\LockException;
use Nextras\Migrations\LogicException;


class Runner
{
	/** @const modes */
	const MODE_CONTINUE = 'continue';
	const MODE_RESET = 'reset';
	const MODE_INIT = 'init';

	/** @var IPrinter */
	private $printer;

	/** @var array<string, IExtensionHandler> (extension => IExtensionHandler) */
	private $extensionsHandlers = [];

	/** @var list<Group> */
	private $groups = [];

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


	public function addGroup(Group $group): self
	{
		$this->groups[] = $group;
		return $this;
	}


	public function addExtensionHandler(string $extension, IExtensionHandler $handler): self
	{
		if (isset($this->extensionsHandlers[$extension])) {
			throw new LogicException("Extension '$extension' has already been defined.");
		}

		$this->extensionsHandlers[$extension] = $handler;
		return $this;
	}


	/**
	 * @param  self::MODE_*   $mode
	 */
	public function run(string $mode = self::MODE_CONTINUE, ?IConfiguration $config = null): void
	{
		if ($config) {
			foreach ($config->getGroups() as $group) {
				$this->addGroup($group);
			}

			foreach ($config->getExtensionHandlers() as $ext => $handler) {
				$this->addExtensionHandler($ext, $handler);
			}
		}

		if ($mode === self::MODE_INIT) {
			$this->driver->setupConnection();
			$this->printer->printSource($this->driver->getInitTableSource() . "\n");
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$files = $this->orderResolver->resolve([], $this->groups, $files, self::MODE_RESET);
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

			foreach ($toExecute as $file) {
				$time = microtime(true);
				$queriesCount = $this->execute($file);
				$this->printer->printExecute($file, $queriesCount, microtime(true) - $time);
			}

			$this->driver->unlock();
			$this->printer->printDone();

		} catch (LockException $e) {
			$this->printer->printError($e);

		} catch (Exception $e) {
			$this->driver->unlock();
			$this->printer->printError($e);
		}
	}


	public function getExtension(string $name): IExtensionHandler
	{
		if (!isset($this->extensionsHandlers[$name])) {
			throw new LogicException("Extension '$name' not found.");
		}

		return $this->extensionsHandlers[$name];
	}


	/**
	 * @return int  number of executed queries
	 */
	protected function execute(File $file): int
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
			throw new ExecutionException(sprintf('Executing migration "%s" has failed.', $file->path), 0, $e);
		}

		$this->driver->markMigrationAsReady($migration);
		$this->driver->commitTransaction();

		return $queriesCount;
	}
}
