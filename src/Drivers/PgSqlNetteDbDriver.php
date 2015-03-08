<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nette\Database\Context;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\LockException;


/**
 * @author Jan Skrasek
 */
class PgSqlNetteDbDriver extends NetteDbDriver
{
    /** @var  string */
    protected $delimitedSchema;

    public function __construct(Context $context, $tableName, $schema = 'public')
    {
        parent::__construct($context, $tableName);
        $this->delimitedSchema = $context->getConnection()->getSupplementalDriver()->delimite($schema);
    }

	public function emptyDatabase()
	{
		$this->context->query("DROP SCHEMA IF EXISTS {$this->delimitedSchema} CASCADE;");
		$this->context->query("CREATE SCHEMA {$this->delimitedSchema};");
	}


	public function createTable()
	{
		$this->context->query($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->context->query("DROP TABLE {$this->delimitedSchema}.{$this->delimitedTableName}");
	}


	public function insertMigration(Migration $migration)
	{
		$row = array(
			'group' => $migration->group,
			'file' => $migration->filename,
			'checksum' => $migration->checksum,
			'executed' => $migration->executedAt,
			'ready' => FALSE,
		);

		$this->context->query("INSERT INTO {$this->delimitedSchema}.{$this->delimitedTableName}", $row);
		$migration->id = $this->context->getConnection()->getInsertId('"migrations_id_seq"');
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->context->query("UPDATE {$this->delimitedSchema}.{$this->delimitedTableName} SET \"ready\" = TRUE WHERE \"id\" = ?", $migration->id);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->context->query("SELECT * FROM {$this->delimitedSchema}.{$this->delimitedTableName}");
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
		return '
CREATE TABLE ' . $this->delimitedSchema . '.' . $this->delimitedTableName . ' (
	"id" serial4 NOT NULL,
	"group" varchar(100) NOT NULL,
	"file" varchar(100) NOT NULL,
	"checksum" char(32) NOT NULL,
	"executed" timestamp NOT NULL,
	"ready" bool NULL,
	PRIMARY KEY (id),
	CONSTRAINT "type_file" UNIQUE ("group", "file")
) WITH (OIDS=FALSE);
		';
	}


	public function getInitMigrationsSource(array $files)
	{
		$out = '';
		foreach ($files as $file) {
			$out .= sprintf(
				'INSERT INTO '. $this->delimitedSchema . '.' . $this->delimitedTableName
			. ' ("group", "file", "checksum", "executed", "ready") VALUES'
			. " ('%s', '%s', '%s', '%s', true);\n",
			$file->group->name, $file->name,  $file->checksum, date('Y-m-d H:i:s')
			);
		}
		return $out;
	}


	protected function tryLock()
	{
		try {
			$this->context->query("CREATE TABLE {$this->delimitedSchema}.{$this->delimitedLockTableName} (\"foo\" INT)");
			return TRUE;
		} catch (\PDOException $e) {
			if ($e->getCode() === '42P07') { // already exists
				return FALSE;
			}

			throw $e;
		}
	}


	protected function tryUnlock()
	{
		try {
			$this->context->query("DROP TABLE {$this->delimitedSchema}.{$this->delimitedLockTableName}");
		} catch (\PDOException $e) {
			if ($e->getCode() === '42P01') {
				throw new LockException('Unable to release lock, because it has been already released.');
			}

			throw $e;
		}
	}

}
