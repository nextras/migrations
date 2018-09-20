<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Migration;


/**
 * @author Petr Procházka
 */
interface IPrinter
{
	/**
	 * Print general info about mode.
	 * - reset = Database has been wiped.
	 * - continue = Running new migrations.
	 * @param  string $mode
	 */
	public function printIntro($mode);
	
	/**
	 * List of migrations which executed has been completed.
	 * @param Migration[] $migrations
	 *
	 * @return void
	 */
	public function printExecutedMigrations(array $migrations);
	
	/**
	 * List of migrations which should be executed has been completed.
	 *
	 * @param  File[] $toExecute
	 * @param  bool   $withFileList
	 *
	 * @return void
	 */
	public function printToExecute(array $toExecute, $withFileList = false);


	/**
	 * A migration has been successfully executed.
	 * @param  File $file
	 * @param  int $count number of executed queries
	 * @param  float $time elapsed time in milliseconds
	 */
	public function printExecute(File $file, $count, $time);


	/**
	 * All migrations have been successfully executed.
	 */
	public function printDone();


	/**
	 * An error has occurred during execution of a migration.
	 * @param  Exception $e
	 */
	public function printError(Exception $e);


	/**
	 * Prints init source code.
	 * @param  string $code
	 */
	public function printSource($code);

}
