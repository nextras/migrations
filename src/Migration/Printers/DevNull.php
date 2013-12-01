<?php
namespace Migration\Printers;

use Migration\Exceptions\Exception;
use Migration;


/**
 * /dev/null printer
 *
 * @author Petr Procházka
 */
class DevNull implements Migration\IPrinter
{

	/**
	 * @inheritdoc
	 */
	public function printReset()
	{

	}

	/**
	 * @inheritdoc
	 */
	public function printToExecute(array $toExecute)
	{

	}

	/**
	 * @inheritdoc
	 */
	public function printExecute(Migration\Entities\File $file, $count)
	{

	}

	/**
	 * @inheritdoc
	 */
	public function printDone()
	{

	}

	/**
	 * @inheritdoc
	 */
	public function printError(Exception $e)
	{

	}

}
