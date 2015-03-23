<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use DateTime;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\IDbal;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
class PgSqlDriver extends BaseDriver implements IDriver
{
	/** @var string */
	protected $schema;

	/** @var string */
	protected $primarySequence;

	/** @var string */
	protected $lockTableName;


	/**
	 * @param IDbal  $dbal
	 * @param string $tableName
	 * @param string $schema
	 */
	public function __construct(IDbal $dbal, $tableName = 'migrations', $schema = 'public')
	{
		parent::__construct($dbal, $tableName);
		$this->schema = $dbal->escapeIdentifier($schema);
		$this->primarySequence = $this->dbal->escapeString($tableName . '_id_seq');
		$this->lockTableName = $dbal->escapeIdentifier($tableName . '_lock');
	}


	public function setupConnection()
	{
	}


	public function emptyDatabase()
	{
		$this->dbal->query("DROP SCHEMA IF EXISTS {$this->schema} CASCADE");
		$this->dbal->query("CREATE SCHEMA {$this->schema}");
	}


	public function beginTransaction()
	{
		$this->dbal->query('START TRANSACTION');
	}


	public function commitTransaction()
	{
		$this->dbal->query('COMMIT');
	}


	public function rollbackTransaction()
	{
		$this->dbal->query('ROLLBACK');
	}


	public function lock()
	{
		try {
			$this->dbal->query("CREATE TABLE {$this->schema}.{$this->lockTableName} (\"foo\" INT)");
		} catch (\Exception $e) {
			throw new LockException('Unable to acquire a lock.', NULL, $e);
		}
	}


	public function unlock()
	{
		try {
			$this->dbal->query("DROP TABLE IF EXISTS {$this->schema}.{$this->lockTableName}");
		} catch (\Exception $e) {
			throw new LockException('Unable to release a lock.', NULL, $e);
		}
	}


	public function createTable()
	{
		$this->dbal->query($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->dbal->query("DROP TABLE {$this->schema}.{$this->tableName}");
	}


	public function insertMigration(Migration $migration)
	{
		$this->dbal->query("
			INSERT INTO {$this->schema}.{$this->tableName}" . '
			("group", "file", "checksum", "executed", "ready") VALUES (' .
				$this->dbal->escapeString($migration->group) . "," .
				$this->dbal->escapeString($migration->filename) . "," .
				$this->dbal->escapeString($migration->checksum) . "," .
				$this->dbal->escapeDateTime($migration->executedAt) . "," .
				$this->dbal->escapeBool(FALSE) .
			")
		");

		$migration->id = $this->dbal->query('SELECT CURRVAL('. $this->primarySequence . ') AS id')[0]['id'];
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->dbal->query("
			UPDATE {$this->schema}.{$this->tableName}" . '
			SET "ready" = TRUE
			WHERE "id" = ' . $this->dbal->escapeInt($migration->id)
		);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->dbal->query("SELECT * FROM {$this->schema}.{$this->tableName}");
		foreach ($result as $row) {
			$migration = new Migration;
			$migration->id = $row['id'];
			$migration->group = $row['group'];
			$migration->filename = $row['file'];
			$migration->checksum = $row['checksum'];
			$migration->executedAt = $row['executed'];
			$migration->completed = (bool) $row['ready'];

			$migrations[] = $migration;
		}

		return $migrations;
	}


	public function getInitTableSource()
	{
		return preg_replace('#^\t{3}#m', '', trim("
			CREATE TABLE {$this->schema}.{$this->tableName} (" . '
				"id" serial4 NOT NULL,
				"group" varchar(100) NOT NULL,
				"file" varchar(100) NOT NULL,
				"checksum" char(32) NOT NULL,
				"executed" timestamp NOT NULL,
				"ready" boolean NOT NULL DEFAULT FALSE,
				PRIMARY KEY ("id"),
				CONSTRAINT "type_file" UNIQUE ("group", "file")
			) WITH (OIDS=FALSE);
		'));
	}


	public function getInitMigrationsSource(array $files)
	{
		$out = '';
		foreach ($files as $file) {
			$out .= "INSERT INTO {$this->schema}.{$this->tableName} ";
			$out .= '("group", "file", "checksum", "executed", "ready") VALUES (' .
					$this->dbal->escapeString($file->group->name) . ", " .
					$this->dbal->escapeString($file->name) . ", " .
					$this->dbal->escapeString($file->checksum) . ", " .
					$this->dbal->escapeDateTime(new DateTime('now')) . ", " .
					$this->dbal->escapeBool(TRUE) .
				");\n";
		}
		return $out;
	}

}
