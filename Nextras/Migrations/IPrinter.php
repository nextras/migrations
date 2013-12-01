<?php
namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;

/**
 * @author Petr Procházka
 */
interface IPrinter
{

	/**
	 * Database has been wiped. Occurs only in reset mode.
	 *
	 * @return void
	 */
	public function printReset();

	/**
	 * List of migrations which should be executed has been completed.
	 *
	 * @param  File[]
	 * @return void
	 */
	public function printToExecute(array $toExecute);

	/**
	 * A migration has been successfully executed.
	 *
	 * @param  File
	 * @param  int  number of executed queries
	 * @return void
	 */
	public function printExecute(File $file, $count);

	/**
	 * All migrations have been successfully executed.
	 *
	 * @return void
	 */
	public function printDone();

	/**
	 * An error has occured during execution of a migration.
	 *
	 * @param  Exception
	 * @return void
	 */
	public function printError(Exception $e);

}
