<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;


/**
 * @author Petr Procházka
 */
interface IPrinter
{
	/**
	 * Database has been wiped. Occurs only in reset mode.
	 */
	function printReset();


	/**
	 * List of migrations which should be executed has been completed.
	 * @param  File[] $toExecute
	 */
	function printToExecute(array $toExecute);


	/**
	 * A migration has been successfully executed.
	 * @param  File $file
	 * @param  int  $count number of executed queries
	 */
	function printExecute(File $file, $count);


	/**
	 * All migrations have been successfully executed.
	 */
	function printDone();


	/**
	 * An error has occurred during execution of a migration.
	 * @param  Exception $e
	 */
	function printError(Exception $e);


	/**
	 * Prints init source code.
	 * @param  string $code
	 */
	function printSource($code);

}
