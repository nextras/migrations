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


class EmptyRunTest extends IntegrationTestCase
{
	public function testReset()
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


	protected function getGroups($dir)
	{
		return [];
	}

}


(new EmptyRunTest)->run();
