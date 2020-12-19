<?php declare(strict_types = 1);

/**
 * @testCase
 * @dataProvider ../../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras\Migrations\Engine\Runner;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


class SecondRunTest extends IntegrationTestCase
{
	public function testReset()
	{
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		$this->runner->run(Runner::MODE_RESET);
		Assert::same([
			'Nextras Migrations',
			'RESET',
			'5 migrations need to be executed.',
			'- structures/001.sql; 1 queries; XX s',
			'- structures/002.sql; 1 queries; XX s',
			'- basic-data/003.sql; 2 queries; XX s',
			'- dummy-data/004.sql; 1 queries; XX s',
			'- structures/005.sql; 1 queries; XX s',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}


	public function testContinueOk()
	{
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		$this->runner->run(Runner::MODE_CONTINUE);
		Assert::same([
			'Nextras Migrations',
			'CONTINUE',
			'2 migrations need to be executed.',
			'- dummy-data/004.sql; 1 queries; XX s',
			'- structures/005.sql; 1 queries; XX s',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}


	public function testContinueError()
	{
		$this->driver->loadFile($this->fixtureDir . '/2ok, 1ko.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		Assert::throws(function () {
			$this->runner->run(Runner::MODE_CONTINUE);
		}, 'Nextras\Migrations\LogicException');

		Assert::same([
			'Nextras Migrations',
			'CONTINUE',
			'ERROR: Previously executed migration "basic-data/003.sql" did not succeed. Please fix this manually or reset the migrations.',
		], $this->printer->lines);

		Assert::count(3, $this->driver->getAllMigrations());
	}


	public function testInit()
	{
		$options = Tester\Environment::loadData();
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		$this->runner->run(Runner::MODE_INIT);

		$files = [
			__DIR__ . "/Runner.FirstRun.init.$options[driver].$options[dbal].txt",
			__DIR__ . "/Runner.FirstRun.init.$options[driver].txt",
		];

		foreach ($files as $file) {
			if (is_file($file)) {
				Assert::matchFile($file, $this->printer->out);
				break;
			}
		}
	}

}


(new SecondRunTest)->run();
