<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;


/**
 * /dev/null printer
 * @author Petr Procházka
 */
class DevNull implements IPrinter
{
	/**
	 * @inheritdoc
	 */
	public function printIntro($mode)
	{
	}
	
	/**
	 * @inheritdoc
	 */
	public function printToExecute(array $toExecute, $withFileList = false)
	{
	}
	
	/**
	 * @inheritdoc
	 */
	public function printExecute(File $file, $count, $time)
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
	
	/**
	 * @inheritdoc
	 */
	public function printSource($code)
	{
	}
	
	/**
	 * @inheritdoc
	 */
	public function printExecutedMigrations(array $migrations)
	{
	
	}
}

