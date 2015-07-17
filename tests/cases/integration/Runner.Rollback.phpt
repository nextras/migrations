<?php

/**
 * @testCase
 * @dataProvider ../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\Group;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class RollbackTest extends IntegrationTestCase
{

	protected function getGroups($dir)
	{
		$rollback = new Group();
		$rollback->enabled = TRUE;
		$rollback->name = 'rollback';
		$rollback->directory = $dir . '/rollback';
		$rollback->dependencies = [];

		return [$rollback];
	}


	/**
	 * @param $mode
	 * @return bool table exists
	 */
	private function runInMode($mode)
	{
		try {
			$this->runner->run($mode);
		} catch (\Exception $e) {
		}

		$res = $this->dbal->query('
			SELECT Count(*) ' . $this->dbal->escapeIdentifier('count') . '
			FROM information_schema.tables
			WHERE table_name = ' . $this->dbal->escapeString('rollback') . '
				AND table_schema = ' . $this->dbal->escapeString($this->dbName) . '
		');
		return (bool) $res[0]['count'];
	}


	public function testContinueRollbacksFailingOnly()
	{
		Assert::true($this->runInMode(Runner::MODE_CONTINUE));
		Assert::count(2, $this->driver->getAllMigrations());
	}


	public function testFullRollback()
	{
		$this->driver->createTable();

		Assert::false($this->runInMode(Runner::MODE_CONTINUE_FULL_ROLLBACK));
		Assert::count(0, $this->driver->getAllMigrations());
	}

}


(new RollbackTest)->run();
