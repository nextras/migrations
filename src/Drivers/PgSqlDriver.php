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

	/** @var NULL|string */
	protected $schemaQuoted;


	/**
	 * @param IDbal  $dbal
	 * @param string $tableName
	 * @param string $schema
	 */
	public function __construct(IDbal $dbal, $tableName = 'migrations', $schema = 'public')
	{
		parent::__construct($dbal, $tableName);
		$this->schema = $schema;
	}


	public function setupConnection()
	{
		parent::setupConnection();
		$this->schemaQuoted = $this->dbal->escapeIdentifier($this->schema);
	}


	public function emptyDatabase()
	{
		$this->dbal->exec("DROP SCHEMA IF EXISTS {$this->schemaQuoted} CASCADE");
		$this->dbal->exec("CREATE SCHEMA {$this->schemaQuoted}");
	}


	public function beginTransaction()
	{
		$this->dbal->exec('START TRANSACTION');
	}


	public function commitTransaction()
	{
		$this->dbal->exec('COMMIT');
	}


	public function rollbackTransaction()
	{
		$this->dbal->exec('ROLLBACK');
	}


	public function lock()
	{
		try {
			$this->dbal->exec('SELECT pg_advisory_lock(-2099128779216184107)');

		} catch (\Exception $e) {
			throw new LockException('Unable to acquire a lock.', NULL, $e);
		}
	}


	public function unlock()
	{
		try {
			$this->dbal->exec('SELECT pg_advisory_unlock(-2099128779216184107)');

		} catch (\Exception $e) {
			throw new LockException('Unable to release a lock.', NULL, $e);
		}
	}


	public function createTable()
	{
		$this->dbal->exec($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->dbal->exec("DROP TABLE {$this->schemaQuoted}.{$this->tableNameQuoted}");
	}


	public function insertMigration(Migration $migration)
	{
		$rows = $this->dbal->query("
			INSERT INTO {$this->schemaQuoted}.{$this->tableNameQuoted}" . '
			("group", "file", "checksum", "executed", "ready") VALUES (' .
				$this->dbal->escapeString($migration->group) . "," .
				$this->dbal->escapeString($migration->filename) . "," .
				$this->dbal->escapeString($migration->checksum) . "," .
				$this->dbal->escapeDateTime($migration->executedAt) . "," .
				$this->dbal->escapeBool(FALSE) .
			")
			RETURNING id
		");

		$migration->id = (int) $rows[0]['id'];
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->dbal->exec("
			UPDATE {$this->schemaQuoted}.{$this->tableNameQuoted}" . '
			SET "ready" = TRUE
			WHERE "id" = ' . $this->dbal->escapeInt($migration->id)
		);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->dbal->query("SELECT * FROM {$this->schemaQuoted}.{$this->tableNameQuoted} ORDER BY \"executed\"");
		foreach ($result as $row) {
			if (is_string($row['executed'])) {
				$executedAt = new DateTime($row['executed']);

			} elseif ($row['executed'] instanceof \DateTimeImmutable) {
				$executedAt = new DateTime('@' . $row['executed']->getTimestamp());

			} else {
				$executedAt = $row['executed'];
			}

			$migration = new Migration;
			$migration->id = (int) $row['id'];
			$migration->group = $row['group'];
			$migration->filename = $row['file'];
			$migration->checksum = $row['checksum'];
			$migration->executedAt = $executedAt;
			$migration->completed = (bool) $row['ready'];

			$migrations[] = $migration;
		}

		return $migrations;
	}


	public function getInitTableSource()
	{
		return preg_replace('#^\t{3}#m', '', trim("
			CREATE TABLE IF NOT EXISTS {$this->schemaQuoted}.{$this->tableNameQuoted} (" . '
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
			$out .= "INSERT INTO {$this->schemaQuoted}.{$this->tableNameQuoted} ";
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
