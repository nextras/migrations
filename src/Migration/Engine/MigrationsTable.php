<?php
namespace Migration\Engine;

use DibiConnection;
use Migration\Entities\Migration;


class MigrationsTable
{

	/** @var DibiConnection */
	private $dibi;

	/** @var string */
	private $tableName;

	public function __construct(DibiConnection $dibiConnection, $tableName)
	{
		$this->dibi = $dibiConnection;
		$this->tableName = $tableName;
	}

	public function getName()
	{
		return $this->tableName;
	}

	public function create()
	{
		$this->dibi->query('
			CREATE TABLE IF NOT EXISTS %n', $this->tableName, '(
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`group` varchar(100) NOT NULL,
				`file` varchar(100) NOT NULL,
				`checksum` char(32) NOT NULL,
				`executed` datetime NOT NULL,
				`ready` tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `type_file` (`group`, `file`)
			) ENGINE=InnoDB;
		');
	}

	public function drop()
	{
		$this->dibi->query('DROP TABLE %n', $this->tableName);
	}

	public function insert(array $row)
	{
		$this->dibi->query('INSERT INTO %n', $this->tableName, $row);
		return $this->dibi->getInsertId();
	}

	public function markAsReady($id)
	{
		$this->dibi->query('
			UPDATE %n', $this->tableName, '
			SET [ready] = 1
			WHERE [id] = %i', $id
		);
	}

	/**
	 * @return Migration[]
	 */
	public function getAllMigrations()
	{
		$result = $this->dibi->query('
			SELECT *
			FROM %n', $this->tableName, '
		');

		$result->setRowFactory(function (array $row) {
			$migration = new Migration();
			$migration->id = $row['id'];
			$migration->group = $row['group'];
			$migration->filename = $row['file'];
			$migration->checksum = $row['checksum'];
			$migration->executedAt = $row['executed'];
			$migration->completed = (bool) $row['ready'];
			return $migration;
		});

		return $result->fetchAll();
	}

}
