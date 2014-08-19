<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\LockException;


/**
 * @author Jan Skrasek
 */
class PgSqlNetteDbDriver extends NetteDbDriver
{

	public function emptyDatabase()
	{
		$this->context->query("DROP SCHEMA IF EXISTS public CASCADE;");
		$this->context->query("CREATE SCHEMA public;");
	}


	public function createTable()
	{
		$this->context->query($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->context->query("DROP TABLE public.{$this->delimitedTableName}");
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

		$this->context->query("INSERT INTO public.{$this->delimitedTableName}", $row);
		$migration->id = $this->context->getConnection()->getInsertId('"migrations_id_seq"');
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->context->query("UPDATE public.{$this->delimitedTableName} SET \"ready\" = TRUE WHERE \"id\" = ?", $migration->id);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->context->query("SELECT * FROM public.{$this->delimitedTableName}");
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
CREATE TABLE public.' . $this->delimitedTableName . ' (
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
				'INSERT INTO public.' . $this->delimitedTableName
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
			$this->context->query("CREATE TABLE public.{$this->delimitedLockTableName} (\"foo\" INT)");
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
			$this->context->query("DROP TABLE public.{$this->delimitedLockTableName}");
		} catch (\PDOException $e) {
			if ($e->getCode() === '42P01') {
				throw new LockException('Unable to release lock, because it has been already released.');
			}

			throw $e;
		}
	}

}
