<?php declare(strict_types = 1);

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
	 * Prints general info about mode.
	 */
	public function printIntro(string $mode): void;


	/**
	 * List of migrations which should be executed has been completed.
	 *
	 * @param  list<File> $toExecute
	 */
	public function printToExecute(array $toExecute): void;


	/**
	 * A migration has been successfully executed.
	 *
	 * @param  int   $count number of executed queries
	 * @param  float $time  elapsed time in seconds
	 */
	public function printExecute(File $file, int $count, float $time): void;


	/**
	 * All migrations have been successfully executed.
	 */
	public function printDone(): void;


	/**
	 * An error has occurred during execution of a migration.
	 */
	public function printError(Exception $e): void;


	/**
	 * Prints init source code.
	 */
	public function printSource(string $code): void;
}
