<?php

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Mockery;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class BaseDriverTest extends Tester\TestCase
{
	/**
	 * @dataProvider provideLoadFileData
	 */
	public function testLoadFile($content, array $expectedQueries)
	{
		$dbal = Mockery::mock('Nextras\Migrations\IDbal');
		$dbal->shouldReceive('escapeIdentifier')->with('migrations')->andReturn('migrations');

		$driver = Mockery::mock('Nextras\Migrations\Drivers\BaseDriver', array($dbal));
		$driver->shouldDeferMissing();

		foreach ($expectedQueries as $expectedQuery) {
			$dbal->shouldReceive('exec')->once()->ordered()->with($expectedQuery);
		}

		$driver->loadFile(Tester\FileMock::create($content));

		Mockery::close();
		Assert::true(TRUE);
	}


	protected function provideLoadFileData()
	{
		return [
			[
				'SELECT 1', [
					'SELECT 1',
				],
			],
			[
				'SELECT 1; ', [
					'SELECT 1',
				],
			],
			[
				'SELECT 1; SELECT 2;    SELECT 3; ', [
					'SELECT 1',
					' SELECT 2',
					'    SELECT 3',
				],
			],
			[
				'SELECT 1; SELECT 2;    SELECT 3; ', [
					'SELECT 1',
					' SELECT 2',
					'    SELECT 3',
				],
			],
			[
				implode("\n", [
					'SELECT 1;',
					'DELIMITER //',
					'CREATE TRIGGER `users_bu` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN SELECT 1; END; //',
					'DELIMITER ;',
					'SELECT 2;',
				]),
				[
					'SELECT 1',
					"\nCREATE TRIGGER `users_bu` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN SELECT 1; END; ",
					"\nSELECT 2",
				]
			],
			[
				'-- ', [],
			],
			[
				"--\n", [],
			],
			[
				"SELECT 1;\n--", [
					'SELECT 1'
				],
			],
			[
				"SELECT 1;\n--\nSELECT 2;", [
					'SELECT 1',
					"\n--\nSELECT 2",
				],
			],
			[
				implode("\n", [
					'DELIMITER ;;',
					'SELECT 1;;',
					'DELIMITER ;',
					'DELIMITER ;;',
					'SELECT 2;;',
					'DELIMITER ;',
				]),
				[
					"\nSELECT 1",
					"\nSELECT 2",
				]
			],
			[
				implode("\n", [
					'SELECT 1;',
					'DELIMITER ;;',
					'DELIMITER ;',
				]),
				[
					"SELECT 1",
				]
			],
		];
	}
}

$test = new BaseDriverTest();
$test->run();
