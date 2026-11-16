<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use DateTime;
use DateTimeInterface;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;
use Nextras\MultiQueryParser\IMultiQueryParser;
use Nextras\MultiQueryParser\SqliteMultiQueryParser;


/**
 * SQLite 3 driver.
 *
 * SQLite has no advisory locks and Runner wraps each migration in its own transaction,
 * so the lock is an flock() on "<database file>.lock". It is released by the OS even
 * if the process dies. In-memory and temporary databases are private to the connection
 * and are not locked at all.
 *
 * @author Michal Semerák
 */
class SqliteDriver extends BaseDriver implements IDriver
{
	protected const LOCK_TIMEOUT = 3;

	/** @var resource|null */
	protected $lockHandle = null;


	protected function createMultiQueryParser(): IMultiQueryParser
	{
		return new SqliteMultiQueryParser();
	}


	public function setupConnection(): void
	{
		parent::setupConnection();
		$this->dbal->exec('PRAGMA foreign_keys = OFF');
	}


	public function emptyDatabase(): void
	{
		$objects = $this->dbal->query("
			SELECT \"type\", \"name\"
			FROM \"sqlite_master\"
			WHERE \"type\" IN ('view', 'table') AND \"name\" NOT LIKE 'sqlite\\_%' ESCAPE '\\'
			ORDER BY \"type\" = 'table'
		");

		foreach ($objects as $object) {
			$type = strtoupper($object['type']);
			$this->dbal->exec("DROP $type IF EXISTS " . $this->dbal->escapeIdentifier($object['name']));
		}
	}


	public function beginTransaction(): void
	{
		$this->dbal->exec('BEGIN TRANSACTION');
	}


	public function commitTransaction(): void
	{
		$this->dbal->exec('COMMIT');
	}


	public function rollbackTransaction(): void
	{
		$this->dbal->exec('ROLLBACK');
	}


	public function lock(): void
	{
		$file = $this->getDatabaseFile();
		if ($file === null) {
			return;
		}

		$handle = @fopen($file . '.lock', 'c'); // @ - error is converted to LockException
		if ($handle === false) {
			throw new LockException('Unable to acquire a lock.', 0, $this->createLastErrorException());
		}

		$deadline = microtime(true) + static::LOCK_TIMEOUT;
		while (!flock($handle, LOCK_EX | LOCK_NB)) {
			if (microtime(true) >= $deadline) {
				fclose($handle);
				throw new LockException('Unable to acquire a lock.');
			}
			usleep(100_000);
		}

		$this->lockHandle = $handle;
	}


	public function unlock(): void
	{
		if ($this->lockHandle === null) {
			return;
		}

		$handle = $this->lockHandle;
		$this->lockHandle = null;
		if (!flock($handle, LOCK_UN)) {
			fclose($handle);
			throw new LockException('Unable to release a lock.');
		}
		fclose($handle);
	}


	public function createTable(): void
	{
		$this->dbal->exec($this->getInitTableSource());
	}


	public function dropTable(): void
	{
		$this->dbal->exec("DROP TABLE {$this->tableNameQuoted}");
	}


	public function insertMigration(Migration $migration): void
	{
		$this->dbal->exec("
			INSERT INTO {$this->tableNameQuoted}" . '
			("group", "file", "checksum", "executed", "ready") VALUES (' .
				$this->dbal->escapeString($migration->group) . "," .
				$this->dbal->escapeString($migration->filename) . "," .
				$this->dbal->escapeString($migration->checksum) . "," .
				$this->escapeDateTime($migration->executedAt) . "," .
				"0" .
			")
		");

		$migration->id = (int) $this->dbal->query('SELECT last_insert_rowid() AS "id"')[0]['id'];
	}


	public function markMigrationAsReady(Migration $migration): void
	{
		$this->dbal->exec("
			UPDATE {$this->tableNameQuoted}" . '
			SET "ready" = 1
			WHERE "id" = ' . $this->dbal->escapeInt($migration->id)
		);
	}


	public function getAllMigrations(): array
	{
		$migrations = [];
		$result = $this->dbal->query("SELECT * FROM {$this->tableNameQuoted} ORDER BY \"executed\"");
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


	public function getInitTableSource(): string
	{
		return preg_replace('#^\t{3}#m', '', trim("
			CREATE TABLE IF NOT EXISTS {$this->tableNameQuoted} (" . '
				"id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
				"group" varchar(100) NOT NULL,
				"file" varchar(100) NOT NULL,
				"checksum" char(32) NOT NULL,
				"executed" datetime NOT NULL,
				"ready" boolean NOT NULL DEFAULT 0,
				CONSTRAINT "type_file" UNIQUE ("group", "file")
			);
		'));
	}


	public function getInitMigrationsSource(array $files): string
	{
		$out = '';
		foreach ($files as $file) {
			$out .= "INSERT INTO {$this->tableNameQuoted} ";
			$out .= '("group", "file", "checksum", "executed", "ready") VALUES (' .
					$this->dbal->escapeString($file->group->name) . ", " .
					$this->dbal->escapeString($file->name) . ", " .
					$this->dbal->escapeString($file->checksum) . ", " .
					$this->escapeDateTime(new DateTime('now')) . ", " .
					"1" .
				");\n";
		}
		return $out;
	}


	/**
	 * SQLite has no date type and DBALs disagree on the format (Nette and Dibi use a Unix timestamp),
	 * so the driver formats the value itself to keep the column sortable and readable.
	 */
	protected function escapeDateTime(DateTimeInterface $value): string
	{
		return $this->dbal->escapeString($value->format('Y-m-d H:i:s'));
	}


	/**
	 * @return string|null path of the main database file, null for in-memory and temporary databases
	 */
	protected function getDatabaseFile(): ?string
	{
		foreach ($this->dbal->query('PRAGMA database_list') as $row) {
			if ($row['name'] === 'main') {
				return $row['file'] !== '' ? $row['file'] : null;
			}
		}
		return null;
	}


	private function createLastErrorException(): ?\Exception
	{
		$error = error_get_last();
		return $error !== null ? new \RuntimeException($error['message']) : null;
	}
}
