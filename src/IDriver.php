<?php declare(strict_types = 1);

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
 * @author Jan Skrasek
 */
interface IDriver
{
	/**
	 * Setups the connection, such as encoding, default schema, etc.
	 */
	public function setupConnection(): void;


	/**
	 * Drops the database / schema. Should remove all db objects (tables, views, procedures, sequences, ...)
	 */
	public function emptyDatabase(): void;


	/**
	 * Loads and executes SQL queries from given file.
	 *
	 * @return int number of executed queries
	 */
	public function loadFile(string $path): int;


	/**
	 * Starts transaction.
	 */
	public function beginTransaction(): void;


	/**
	 * Commit transaction.
	 */
	public function commitTransaction(): void;


	/**
	 * Rollback transaction.
	 */
	public function rollbackTransaction(): void;


	/**
	 * Locks database for running migrations.
	 */
	public function lock(): void;


	/**
	 * Unlocks database.
	 */
	public function unlock(): void;


	/**
	 * Creates migration table.
	 */
	public function createTable(): void;


	/**
	 * Drop migration table.
	 */
	public function dropTable(): void;


	/**
	 * Inserts migration info into migration table.
	 */
	public function insertMigration(Migration $migration): void;


	/**
	 * Updated migration as executed.
	 */
	public function markMigrationAsReady(Migration $migration): void;


	/**
	 * Returns all migrations stored in migration table sorted by time.
	 *
	 * @return list<Migration>
	 */
	public function getAllMigrations(): array;


	/**
	 * Returns source code for migration table initialization.
	 */
	public function getInitTableSource(): string;


	/**
	 * Returns source code for migration table data initialization.
	 *
	 * @param  list<File> $files
	 */
	public function getInitMigrationsSource(array $files): string;
}
