<?php
namespace Migration;

use Migration\Entities\File;
use Migration\Exceptions\Exception;

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
	 * @param  Entities\File[]
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
