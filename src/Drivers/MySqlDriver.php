<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use DateTime;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
class MySqlDriver extends BaseDriver implements IDriver
{
	private const LOCK_NAME = 'Nextras.Migrations';


	public function setupConnection(): void
	{
		parent::setupConnection();
		$this->dbal->exec('SET NAMES "utf8mb4"');
		$this->dbal->exec('SET foreign_key_checks = 0');
		$this->dbal->exec('SET time_zone = "SYSTEM"');
		$this->dbal->exec('SET sql_mode = "TRADITIONAL"');
	}


	public function emptyDatabase(): void
	{
		$rows = $this->dbal->query('SELECT DATABASE() AS `name`');
		$dbName = $this->dbal->escapeIdentifier($rows[0]['name']);

		$rows = $this->dbal->query('SHOW VARIABLES LIKE "collation_database"');
		$collate = ($rows ? 'COLLATE=' . $this->dbal->escapeString($rows[0]['Value']) : '');

		$this->dbal->exec("DROP DATABASE $dbName");
		$this->dbal->exec("CREATE DATABASE $dbName $collate");
		$this->dbal->exec("USE $dbName");
	}


	public function beginTransaction(): void
	{
		$this->dbal->exec('START TRANSACTION');
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
		$lock = $this->dbal->escapeString(self::LOCK_NAME);
		$result = (int) $this->dbal->query("SELECT GET_LOCK(SHA1(CONCAT($lock, '-', DATABASE())), 3) AS `result`")[0]['result'];
		if ($result !== 1) {
			throw new LockException('Unable to acquire a lock.');
		}
	}


	public function unlock(): void
	{
		$lock = $this->dbal->escapeString(self::LOCK_NAME);
		$result = (int) $this->dbal->query("SELECT RELEASE_LOCK(SHA1(CONCAT($lock, '-', DATABASE()))) AS `result`")[0]['result'];
		if ($result !== 1) {
			throw new LockException('Unable to release a lock.');
		}
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
			INSERT INTO {$this->tableNameQuoted}
			(`group`, `file`, `checksum`, `executed`, `ready`) VALUES (" .
				$this->dbal->escapeString($migration->group) . "," .
				$this->dbal->escapeString($migration->filename) . "," .
				$this->dbal->escapeString($migration->checksum) . "," .
				$this->dbal->escapeDateTime($migration->executedAt) . "," .
				$this->dbal->escapeBool(false) .
			")
		");

		$migration->id = (int) $this->dbal->query('SELECT LAST_INSERT_ID() AS `id`')[0]['id'];
	}


	public function markMigrationAsReady(Migration $migration): void
	{
		$this->dbal->exec("
			UPDATE {$this->tableNameQuoted}
			SET `ready` = 1
			WHERE `id` = " . $this->dbal->escapeInt($migration->id)
		);
	}


	public function getAllMigrations(): array
	{
		$migrations = [];
		$result = $this->dbal->query("SELECT * FROM {$this->tableNameQuoted} ORDER BY `executed`");
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
			CREATE TABLE IF NOT EXISTS {$this->tableNameQuoted} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`group` varchar(100) NOT NULL,
				`file` varchar(100) NOT NULL,
				`checksum` char(32) NOT NULL,
				`executed` datetime NOT NULL,
				`ready` tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `type_file` (`group`, `file`)
			) ENGINE=InnoDB;
		"));
	}


	public function getInitMigrationsSource(array $files): string
	{
		$out = '';
		foreach ($files as $file) {
			$out .= "INSERT INTO {$this->tableNameQuoted} ";
			$out .= "(`group`, `file`, `checksum`, `executed`, `ready`) VALUES (" .
				$this->dbal->escapeString($file->group->name) . ", " .
				$this->dbal->escapeString($file->name) . ", " .
				$this->dbal->escapeString($file->checksum) . ", " .
				$this->dbal->escapeDateTime(new DateTime('now')) . ", " .
				$this->dbal->escapeBool(true) .
			");\n";
		}
		return $out;
	}
}
