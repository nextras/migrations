<?php
namespace Nextras\Migrations;

use Nextras\Migrations\Entities\Migration;


interface IDriver
{

	public function setupConnection();

	public function emptyDatabase();

	public function beginTransaction();

	public function commitTransaction();

	public function rollbackTransaction();

	public function lock();

	public function unlock();

	public function createTable();

	public function dropTable();

	public function insertMigration(Migration $migration);

	public function markMigrationAsReady(Migration $migration);

	public function getAllMigrations();

}
