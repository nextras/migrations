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
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
class MySqlDriver extends BaseDriver implements IDriver
{

	public function setupConnection()
	{
		$this->dbal->query('SET NAMES "utf8"');
		$this->dbal->query('SET foreign_key_checks = 0');
		$this->dbal->query('SET time_zone = "SYSTEM"');
		$this->dbal->query('SET sql_mode = "TRADITIONAL"');
	}


	public function emptyDatabase()
	{
		$rows = $this->dbal->query('SELECT DATABASE() AS `name`');
		$dbName = $this->dbal->escapeIdentifier($rows[0]['name']);

		$rows = $this->dbal->query('SHOW VARIABLES LIKE "collation_database"');
		$collate = ($rows ? 'COLLATE=' . $this->dbal->escapeString($rows[0]['Value']) : '');

		$this->dbal->query("DROP DATABASE $dbName");
		$this->dbal->query("CREATE DATABASE $dbName $collate");
		$this->dbal->query("USE $dbName");
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
		$lock = $this->dbal->escapeString(self::LOCK_NAME);
		$result = $this->dbal->query("SELECT GET_LOCK($lock, 3) AS `result`")[0]['result'];
		if ($result !== 1) {
			throw new LockException('Unable to acquire a lock.');
		}
	}


	public function unlock()
	{
		$lock = $this->dbal->escapeString(self::LOCK_NAME);
		$result = $this->dbal->query("SELECT RELEASE_LOCK($lock) AS `result`")[0]['result'];
		if ($result !== 1) {
			throw new LockException('Unable to release a lock.');
		}
	}


	public function createTable()
	{
		$this->dbal->query($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->dbal->query("DROP TABLE {$this->tableName}");
	}


	public function insertMigration(Migration $migration)
	{
		$this->dbal->query("
			INSERT INTO {$this->tableName}
			(`group`, `file`, `checksum`, `executed`, `ready`) VALUES (" .
				$this->dbal->escapeString($migration->group) . "," .
				$this->dbal->escapeString($migration->filename) . "," .
				$this->dbal->escapeString($migration->checksum) . "," .
				$this->dbal->escapeDateTime($migration->executedAt) . "," .
				$this->dbal->escapeBool(FALSE) .
			")
		");

		$migration->id = $this->dbal->query('SELECT LAST_INSERT_ID() AS `id`')[0]['id'];
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->dbal->query("
			UPDATE {$this->tableName}
			SET `ready` = 1
			WHERE `id` = " . $this->dbal->escapeInt($migration->id)
		);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->dbal->query("SELECT * FROM {$this->tableName}");
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
			CREATE TABLE IF NOT EXISTS {$this->tableName} (
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


	public function getInitMigrationsSource(array $files)
	{
		$out = '';
		foreach ($files as $file) {
			$out .= "INSERT INTO {$this->tableName} ";
			$out .= "(`group`, `file`, `checksum`, `executed`, `ready`) VALUES (" .
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
