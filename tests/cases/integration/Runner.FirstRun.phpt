<?php

/**
 * @testCase
 * @dataProvider ../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras\Migrations\Engine\Runner;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class FirstRunTest extends IntegrationTestCase
{

	public function testReset()
	{
		$this->runner->run(Runner::MODE_RESET);
		Assert::same([
			'RESET',
			'5 migrations need to be executed.',
			'structures/001.sql; 1 queries',
			'structures/002.sql; 1 queries',
			'basic-data/003.sql; 2 queries',
			'dummy-data/004.sql; 1 queries',
			'structures/005.sql; 1 queries',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}


	public function testContinue()
	{
		$this->runner->run(Runner::MODE_CONTINUE);
		Assert::same([
			'5 migrations need to be executed.',
			'structures/001.sql; 1 queries',
			'structures/002.sql; 1 queries',
			'basic-data/003.sql; 2 queries',
			'dummy-data/004.sql; 1 queries',
			'structures/005.sql; 1 queries',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}


	public function testInit()
	{
		$options = Tester\Environment::loadData();
		$this->runner->run(Runner::MODE_INIT);
		Assert::matchFile(__DIR__ . "/Runner.FirstRun.init.$options[driver].txt", $this->printer->out);
	}
}


(new FirstRunTest)->run();
