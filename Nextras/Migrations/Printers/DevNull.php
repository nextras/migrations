<?php
namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;


/**
 * /dev/null printer
 *
 * @author Petr Procházka
 */
class DevNull implements IPrinter
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
	public function printExecute(File $file, $count)
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
