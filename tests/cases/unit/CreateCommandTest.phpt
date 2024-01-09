<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations\Bridges\SymfonyConsole;

use Nextras;
use Nextras\Migrations\Bridges\SymfonyConsole\CreateCommand;
use Nextras\Migrations\Configurations\DefaultConfiguration;
use Mockery;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class CreateCommandTest extends Tester\TestCase
{
	public function testTypeArgDescription(): void
	{
		$driver = Mockery::mock(Nextras\Migrations\IDriver::class);
		$config = new DefaultConfiguration('migrations', $driver);

		$command = new CreateCommand($driver, $config);
		$description = $command->getDefinition()->getArgument('type')->getDescription();
		Assert::same('b(asic-data), d(ummy-data) or s(tructures)', $description);
	}
}

$test = new CreateCommandTest();
$test->run();
