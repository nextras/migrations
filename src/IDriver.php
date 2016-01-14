<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\Entities\File;


/**
 * @author Jan Skrasek
 */
interface IDriver
{
	/** @const shared lock identifier */
	const LOCK_NAME = 'Nextras.Migrations';


	/**
	 * Setups the connection, such as encoding, default schema, etc.
	 */
	function setupConnection();


	/**
	 * Drops the database / schema. Should removes all db objects (tables, views, procedures, sequences, ...)
	 * @return mixed
	 */
	function emptyDatabase();


	/**
	 * Loads and executes SQL queries from given file.
	 * @param  string $path
	 * @return int number of executed queries
	 */
	function loadFile($path);


	/**
	 * Starts transaction.
	 */
	function beginTransaction();


	/**
	 * Commit transaction.
	 */
	function commitTransaction();


	/**
	 * Rollback transaction.
	 */
	function rollbackTransaction();


	/**
	 * Locks database for running migrations.
	 */
	function lock();


	/**
	 * Unlocks database.
	 */
	function unlock();


	/**
	 * Creates migration table.
	 */
	function createTable();


	/**
	 * Drop migration table.
	 */
	function dropTable();


	/**
	 * Inserts migration info into migration table.
	 * @param  Migration $migration
	 */
	function insertMigration(Migration $migration);


	/**
	 * Updated migration as executed.
	 * @param  Migration $migration
	 */
	function markMigrationAsReady(Migration $migration);


	/**
	 * Returns all migrations stored in migration table sorted by time.
	 * @return Migration[]
	 */
	function getAllMigrations();


	/**
	 * Returns source code for migration table initialization.
	 * @return string
	 */
	function getInitTableSource();


	/**
	 * Returns source code for migration table data initialization.
	 * @param  File[] $files
	 * @return string
	 */
	function getInitMigrationsSource(array $files);

}
