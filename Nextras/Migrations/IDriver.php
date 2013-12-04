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

	/**
	 * Setups the connection, such as encoding, default schema, etc.
	 */
	function setupConnection();


	/**
	 * Drops the database / schema. Shoudl removes all db objects (tables, views, procedures, sequences, ...)
	 * @return mixed
	 */
	function emptyDatabase();


	/**
	 * Starts transaction.
	 */
	function beginTransaction();


	/**
	 * Commit transaction.
	 */
	function commitTransaction();


	/**
	 * Rollback transation.
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
	 * @param  Migration
	 */
	function insertMigration(Migration $migration);


	/**
	 * Updated migration as executed.
	 * @param  Migration
	 */
	function markMigrationAsReady(Migration $migration);


	/**
	 * Returns all migrations stored in migration table.
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
	 * @param  File[]
	 * @return string
	 */
	function getInitMigrationsSource(array $files);

}
