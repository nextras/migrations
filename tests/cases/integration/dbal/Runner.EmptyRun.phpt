<?php declare(strict_types = 1);

/**
 * @testCase
 * @dataProvider ../../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Nextras\Migrations\Engine\Runner;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


class EmptyRunTest extends IntegrationTestCase
{
	public function testReset(): void
	{
		$this->runner->run(Runner::MODE_RESET);
		Assert::same([
			'Nextras Migrations',
			'RESET',
			'No migration needs to be executed.',
			'OK',
		], $this->printer->lines);

		Assert::count(0, $this->driver->getAllMigrations());
	}


	protected function getGroups(string $dir): array
	{
		return [];
	}

}


(new EmptyRunTest)->run();
