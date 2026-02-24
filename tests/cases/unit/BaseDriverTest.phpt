<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras;
use Nextras\MultiQueryParser\MySqlMultiQueryParser;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class BaseDriverTest extends Tester\TestCase
{
	/**
	 * @dataProvider provideLoadFileData
	 */
	public function testLoadFile(string $content, array $expectedQueries): void
	{
		$dbal = Mockery::mock(Nextras\Migrations\IDbal::class);
		$dbal->shouldReceive('escapeIdentifier')->with('migrations')->andReturn('migrations');

		$driver = Mockery::mock(Nextras\Migrations\Drivers\BaseDriver::class, array($dbal));
		$driver->shouldAllowMockingProtectedMethods();
		$driver->shouldDeferMissing();
		$driver->shouldReceive('createMultiQueryParser')->andReturn(new MySqlMultiQueryParser());

		foreach ($expectedQueries as $expectedQuery) {
			$dbal->shouldReceive('exec')->once()->ordered()->with($expectedQuery);
		}

		$driver->loadFile(Tester\FileMock::create($content));

		Mockery::close();
		Assert::true(true);
	}


	protected function provideLoadFileData(): array
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
					'SELECT 2',
					'SELECT 3',
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
					'CREATE TRIGGER `users_bu` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN SELECT 1; END; ',
					'SELECT 2',
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
					'SELECT 2',
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
					'SELECT 1',
					'SELECT 2',
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
