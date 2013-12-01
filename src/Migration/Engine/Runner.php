<?php
namespace Migration\Engine;

use DateTime;
use DibiConnection;
use Migration\Entities\File;
use Migration\Entities\Group;
use Migration\Exceptions\Exception;
use Migration\Exceptions\ExecutionException;
use Migration\Exceptions\LoginException;
use Migration\IExtensionHandler;
use Migration\IPrinter;


class Runner
{

	/** modes */
	const MODE_CONTINUE = 'continue';
	const MODE_RESET = 'reset';

	/** @var IPrinter */
	private $printer;

	/** @var array (extension => IExtensionHandler) */
	private $extensionsHandlers = array();

	/** @var Group[] */
	private $groups = array();

	/** @var DatabaseHelpers */
	private $dbHelper;

	/** @var MigrationsTable */
	private $table;

	/** @var Finder */
	private $finder;

	/** @var OrderResolver */
	private $orderResolver;

	public function __construct(DibiConnection $dibi, IPrinter $printer)
	{
		$this->printer = $printer;
		$this->dbHelper = new DatabaseHelpers($dibi);
		$this->table = new MigrationsTable($dibi, 'migrations');
		$this->finder = new Finder();
		$this->orderResolver = new OrderResolver();
	}

	public function addGroup(Group $group)
	{
		$this->groups[] = $group;
		return $this;
	}

	/**
	 * @param  string
	 * @param  IExtension
	 * @return self
	 */
	public function addExtensionHandler($extension, IExtensionHandler $handler)
	{
		if (isset($this->extensionsHandlers[$extension]))
		{
			throw new LoginException("Extension '$extension' has already been defined.");
		}

		$this->extensionsHandlers[$extension] = $handler;
		return $this;
	}

	/**
	 * @param string self::MODE_CONTINUE or self::MODE_RESET
	 */
	public function run($mode = self::MODE_CONTINUE)
	{
		try
		{
			$this->dbHelper->setup();
			$this->dbHelper->lock();
			if ($mode === self::MODE_RESET)
			{
				$this->dbHelper->wipeDatabase();
				$this->printer->printReset();
			}

			$this->table->create();
			$migrations = $this->table->getAllMigrations();
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$toExecute = $this->orderResolver->resolve($migrations, $this->groups, $files, $mode);
			$this->printer->printToExecute($toExecute);

			foreach ($toExecute as $file)
			{
				$queriesCount = $this->execute($file);
				$this->printer->printExecute($file, $queriesCount);
			}

			$this->printer->printDone();
		}
		catch (Exception $e)
		{
			$this->printer->printError($e);
		}
	}


	/**
	 * @param string
	 * @return IExtensionHandler
	 */
	public function getExtension($name)
	{
		if (!isset($this->extensionsHandlers[$name]))
		{
			throw new LoginException("Extension '$name' not found.");
		}
		return $this->extensionsHandlers[$name];
	}

	/**
	 * @param  File
	 * @return int  number of executed queries
	 */
	protected function execute(File $file)
	{
		$this->dbHelper->beginTransaction();
		// Note: MySQL implicitly commits after some operations, such as CREATE or ALTER TABLE, see http://dev.mysql.com/doc/refman/5.6/en/implicit-commit.html
		// proto se radeji kontroluje jestli bylo dokonceno

		$id = $this->table->insert(array(
			'group' => $file->group->name,
			'file' => $file->name,
			'checksum' => $file->checksum,
			'executed' => new DateTime('now'),
			'ready' => (int) FALSE,
		));

		try
		{
			$queriesCount = $this->getExtension($file->extension)->execute($file);
		}
		catch (\Exception $e)
		{
			$this->dbHelper->rollbackTransaction();
			throw new ExecutionException(sprintf('Executing migration "%s" has failed.', $file->getPath()), NULL, $e);
		}

		$this->table->markAsReady($id);
		$this->dbHelper->commitTransaction();

		return $queriesCount;
	}

}
