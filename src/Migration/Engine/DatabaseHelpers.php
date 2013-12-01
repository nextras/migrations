<?php
namespace Migration\Engine;

use DibiConnection;
use DibiDriverException;
use Migration;
use Migration\Exceptions\LockException;
use Migration\Exceptions\LogicException;


class DatabaseHelpers
{

	/** @var DibiConnection  */
	private $dibi;

	/** @var bool */
	private $locked = FALSE;

	public function __construct(DibiConnection $dibiConnection)
	{
		$this->dibi = $dibiConnection;
	}

	public function __destruct()
	{
		if ($this->locked)
		{
			$this->unlock();
		}
	}

	/**
	 * Configures database for importing
	 */
	public function setup()
	{
		$this->dibi->query('SET NAMES %s', 'utf8');
		$this->dibi->query('SET foreign_key_checks = %i', 0);
		$this->dibi->query('SET time_zone = %s', 'SYSTEM');
		$this->dibi->query('SET sql_mode = %s', 'TRADITIONAL');
	}

	/**
	 * Drops all tables and views in database.
	 *
	 * @return array (0 => number of dropped tables, 1 => number of dropped views)
	 */
	public function wipeDatabase()
	{
		$tablesCount = 0;
		$viewsCount = 0;

		foreach ($this->dibi->getDatabaseInfo()->getTables() as $table)
		{
			if ($table->getName() === 'migrations_lock') continue;

			if ($table->isView())
			{
				$viewsCount++;
				$type = 'VIEW';
			}
			else
			{
				$tablesCount++;
				$type = 'TABLE';
			}

			$this->dibi->query('DROP %sql %n', $type, $table->getName());
		}

		return array($tablesCount, $viewsCount);
	}

	/**
	 * Begins a transaction.
	 *
	 * @return void
	 */
	public function beginTransaction()
	{
		$this->dibi->begin();
	}

	/**
	 * Commits statements in a transaction.
	 *
	 * @return void
	 */
	public function commitTransaction()
	{
		$this->dibi->commit();
	}

	/**
	 * Rollback changes in a transaction.
	 *
	 * @return void
	 */
	public function rollbackTransaction()
	{
		$this->dibi->rollback();
	}

	/**
	 * Acquires unique lock.
	 *
	 * @return void
	 * @throws \Migration\Exceptions\LockException if acquiring lock fails
	 */
	public function lock()
	{
		$times = 0;
		while (!$this->tryLock())
		{
			if (++$times >= 3) throw new LockException('Unable to acquire a lock.');
			sleep(1); // in seconds
		}
	}

	/**
	 * Releases lock.
	 *
	 * @return void
	 * @throws \Migration\Exceptions\LogicException if we don't hold the lock
	 * @throws \Migration\Exceptions\LockException if the lock is already released
	 */
	public function unlock()
	{
		if (!$this->locked)
		{
			throw new LogicException('A lock must be acquired before it can be released.');
		}

		try
		{
			$this->dibi->query('DROP TABLE [migrations_lock]');
			$this->locked = FALSE;
		}
		catch (DibiDriverException $e)
		{
			// Unknown table '%s' in %s
			// http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
			if ($e->getCode() === 1051)
			{
				throw new LockException('Unable to release lock, because it has been already released.');
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Tries to acquire lock.
	 *
	 * @return bool true if acquiring lock was successful, false otherwise
	 */
	private function tryLock()
	{
		try
		{
			$this->dibi->query('CREATE TABLE [migrations_lock] ([foo] INT)');
			$this->locked = TRUE;
			return TRUE;
		}
		catch (DibiDriverException $e)
		{
			// Table '%s' already exists
			// http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_table_exists_error
			if ($e->getCode() === 1050)
			{
				return FALSE;
			}
			else
			{
				throw $e;
			}
		}
	}

}
