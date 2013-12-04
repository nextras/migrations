<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nette\Database\Context;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;
use Nextras\Migrations\LogicException;


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
abstract class NetteDbDriver implements IDriver
{
	/** @var Context */
	protected $context;

	/** @var string */
	protected $delimitedTableName;

	/** @var string */
	protected $delimitedLockTableName;

	/** @var bool */
	private $locked = FALSE;


	public function __construct(Context $context, $tableName)
	{
		$this->context = $context;
		$this->delimitedTableName = $context->getConnection()->getSupplementalDriver()->delimite($tableName);
		$this->delimitedLockTableName = $context->getConnection()->getSupplementalDriver()->delimite($tableName . '_lock');
	}


	public function __destruct()
	{
		if ($this->locked) {
			$this->unlock();
		}
	}


	public function setupConnection()
	{
	}


	abstract public function emptyDatabase();


	public function beginTransaction()
	{
		$this->context->beginTransaction();
	}


	public function commitTransaction()
	{
		$this->context->commit();
	}


	public function rollbackTransaction()
	{
		$this->context->rollBack();
	}


	public function lock()
	{
		$times = 0;

		while (!$this->tryLock()) {
			if (++$times >= 3) {
				throw new LockException('Unable to acquire a lock.');
			}
			sleep(1); // in seconds
		}
		$this->locked = TRUE;
	}


	public function unlock()
	{
		if (!$this->locked) {
			throw new LogicException('A lock must be acquired before it can be released.');
		}

		$this->tryUnlock();
		$this->locked = FALSE;
	}

	abstract protected function tryLock();

	abstract protected function tryUnlock();

}
