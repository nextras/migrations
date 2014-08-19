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
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
class MySqlNetteDbDriver extends NetteDbDriver
{

	public function setupConnection()
	{
		$this->context->query('SET NAMES ?', 'utf8');
		$this->context->query('SET foreign_key_checks = ?', 0);
		$this->context->query('SET time_zone = ?', 'SYSTEM');
		$this->context->query('SET sql_mode = ?', 'TRADITIONAL');
	}


	public function emptyDatabase()
	{
		$dbName = $this->context->fetchField('SELECT DATABASE()');
		$dbName = $this->context->getConnection()->getSupplementalDriver()->delimite($dbName);

		$collation = $this->context->fetch('SHOW VARIABLES LIKE "collation_database"');
		if ($collation) {
			$collation = $collation->Value;
		}

		$this->context->query('DROP DATABASE ' . $dbName);
		$this->context->query('CREATE DATABASE ' . $dbName . ($collation ? (' COLLATE="' . $collation . '"') : ''));
		$this->context->query('USE ' . $dbName);
	}


	public function createTable()
	{
		$this->context->query($this->getInitTableSource());
	}


	public function dropTable()
	{
		$this->context->query("DROP TABLE {$this->delimitedTableName}");
	}


	public function insertMigration(Migration $migration)
	{
		$row = array(
			'group' => $migration->group,
			'file' => $migration->filename,
			'checksum' => $migration->checksum,
			'executed' => $migration->executedAt,
			'ready' => 0,
		);

		$this->context->query("INSERT INTO {$this->delimitedTableName}", $row);
		$migration->id = $this->context->getConnection()->getInsertId();
	}


	public function markMigrationAsReady(Migration $migration)
	{
		$this->context->query("UPDATE {$this->delimitedTableName} SET `ready` = 1 WHERE `id` = ?", $migration->id);
	}


	public function getAllMigrations()
	{
		$migrations = array();
		$result = $this->context->query("SELECT * FROM {$this->delimitedTableName}");
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
		return "
CREATE TABLE IF NOT EXISTS {$this->delimitedTableName} (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`group` varchar(100) NOT NULL,
	`file` varchar(100) NOT NULL,
	`checksum` char(32) NOT NULL,
	`executed` datetime NOT NULL,
	`ready` tinyint(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `type_file` (`group`, `file`)
) ENGINE=InnoDB;
		";
	}


	public function getInitMigrationsSource(array $files)
	{
		$out = '';
		foreach ($files as $file) {
			$out .= sprintf(
				'INSERT INTO ' . $this->delimitedTableName
			. ' (`group`, `file`, `checksum`, `executed`, `ready`) VALUES'
			. " ('%s', '%s', '%s', '%s', 1);\n",
			$file->group->name, $file->name,  $file->checksum, date('Y-m-d H:i:s')
			);
		}
		return $out;
	}


	protected function tryLock()
	{
		try {
			$this->context->query("CREATE TABLE {$this->delimitedLockTableName} (`foo` INT)");
			return TRUE;
		} catch (\PDOException $e) {
			if ($e->getCode() === '42S01') { // already exists
				return FALSE;
			}

			throw $e;
		}
	}


	protected function tryUnlock()
	{
		try {
			$this->context->query("DROP TABLE {$this->delimitedLockTableName}");
		} catch (\PDOException $e) {
			if ($e->getCode() === '42S02') {
				throw new LockException('Unable to release lock, because it has been already released.');
			}

			throw $e;
		}
	}

}
