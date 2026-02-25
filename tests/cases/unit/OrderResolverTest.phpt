<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Nextras;
use Nextras\Migrations\Engine\OrderResolver;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Migration;
use Mockery;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class OrderResolverTest extends Tester\TestCase
{
	public function testFirstRun(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);

		// 1s* 2s*
		Assert::same([$fileA, $fileB], $resolver->resolve(
			[],
			[$groupA],
			[$fileB, $fileA],
			Runner::MODE_CONTINUE
		));
	}


	public function testFirstRunTwoGroups(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('1g');
		$groupB = $this->createGroup('2g');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupB);
		$fileC = $this->createFile('3s', $groupA);

		// 1s* 2s* 3s*
		Assert::same([$fileA, $fileB, $fileC], $resolver->resolve(
			[],
			[$groupA, $groupB],
			[$fileC, $fileB, $fileA],
			Runner::MODE_CONTINUE
		));
	}


	public function testSecondRunContinue(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);

		// 1s 2s*
		Assert::same([$fileB], $resolver->resolve(
			[$migrationA],
			[$groupA],
			[$fileB, $fileA],
			Runner::MODE_CONTINUE
		));
	}


	public function testSecondRunContinueNothingToDo(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s');
		$migrationB = $this->createMigration($groupA->name, '2s');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);

		// 1s 2s
		Assert::same([], $resolver->resolve(
			[$migrationA, $migrationB],
			[$groupA],
			[$fileB, $fileA],
			Runner::MODE_CONTINUE
		));
	}


	public function testSecondRunContinueTwoGroups(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$groupB = $this->createGroup('data');

		$migrationA = $this->createMigration($groupA->name, '1s');
		$migrationB = $this->createMigration($groupB->name, '2d');

		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2d', $groupB);
		$fileC = $this->createFile('3s', $groupA);
		$fileD = $this->createFile('4d', $groupB);

		// 1s 2d 3s* 4d*
		Assert::same([$fileC, $fileD], $resolver->resolve(
			[$migrationB, $migrationA],
			[$groupA, $groupB],
			[$fileB, $fileA, $fileD, $fileC],
			Runner::MODE_CONTINUE
		));
	}


	public function testSecondRunContinueDisabledGroup(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$groupB = $this->createGroup('data', false);

		$migrationA = $this->createMigration($groupA->name, '1s');
		$migrationB = $this->createMigration($groupB->name, '2d');

		$fileA = $this->createFile('1s', $groupA);
		$fileD = $this->createFile('4s', $groupA);

		// 1s 2d 3d* 4s*
		Assert::same([$fileD], $resolver->resolve(
			[$migrationB, $migrationA],
			[$groupA, $groupB],
			[$fileD, $fileA],
			Runner::MODE_CONTINUE
		));
	}


	public function testSecondRunReset(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);

		Assert::same([$fileA, $fileB], $resolver->resolve(
			[$migrationA],
			[$groupA],
			[$fileB, $fileA],
			Runner::MODE_RESET
		));
	}


	public function testRunWithDisabledGroups(): void
	{
		$groupA = $this->createGroup('structures');
		$groupB = $this->createGroup('data', false, ['structures']);
		$groupC = $this->createGroup('test-data', false, ['data']);

		$method = new \ReflectionMethod(Nextras\Migrations\Engine\OrderResolver::class, 'validateGroups');
		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}
		$method->invoke(new OrderResolver, [
			'structures' => $groupA,
			'data' => $groupB,
			'test-data' => $groupC,
		]);
		Tester\Environment::$checkAssertions = false;
	}


	public function testTopologicalOrder(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('structures');
		$groupB = $this->createGroup('data', true, ['structures']);

		$fileA = $this->createFile('foo', $groupA);
		$fileB = $this->createFile('foo', $groupB);

		Assert::same([$fileA, $fileB], $resolver->resolve(
			[],
			[$groupA, $groupB],
			[$fileA, $fileB],
			Runner::MODE_CONTINUE
		));
	}


	public function testIndependentGroupsOrder1(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('a');
		$groupB = $this->createGroup('b');

		$migrationB = $this->createMigration($groupB->name, '2b');

		$fileA = $this->createFile('1a', $groupA);
		$fileB = $this->createFile('2b', $groupB);

		// 1a* 2b
		Assert::same([$fileA], $resolver->resolve(
			[$migrationB],
			[$groupA, $groupB],
			[$fileA, $fileB],
			Runner::MODE_CONTINUE
		));
	}


	public function testIndependentGroupsOrder2(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('a');
		$groupB = $this->createGroup('b', true, ['a']);
		$groupC = $this->createGroup('c', true, ['a']);

		$migrationA = $this->createMigration($groupA->name, '1a');
		$migrationC = $this->createMigration($groupC->name, '3c');

		$fileA = $this->createFile('1a', $groupA);
		$fileB = $this->createFile('2b', $groupB);
		$fileC = $this->createFile('3c', $groupC);

		// 1a 2b* 3c
		Assert::same([$fileB], $resolver->resolve(
			[$migrationA, $migrationC],
			[$groupA, $groupB, $groupC],
			[$fileA, $fileB, $fileC],
			Runner::MODE_CONTINUE
		));
	}


	public function testErrorRemovedFile(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s');
		$fileB = $this->createFile('2s', $groupA);

		// 1s 2s*
		Assert::exception(
			fn() => $resolver->resolve([$migrationA], [$groupA], [$fileB], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Previously executed migration "structures/1s" is missing.',
		);
	}


	public function testErrorChangedChecksum(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s', '1s.md5.X');
		$fileA = $this->createFile('1s', $groupA, '1s.md5.Y');
		$fileB = $this->createFile('2s', $groupA);

		// 1s 2s*
		Assert::exception(
			fn() => $resolver->resolve([$migrationA], [$groupA], [$fileB, $fileA], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Previously executed migration "structures/1s" has been changed. File checksum is "1s.md5.Y", but executed migration had checksum "1s.md5.X".',
		);
	}


	public function testErrorIncompleteMigration(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s', null, false);
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);

		// 1s 2s*
		Assert::exception(
			fn() => $resolver->resolve([$migrationA], [$groupA], [$fileB, $fileA], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Previously executed migration "structures/1s" did not succeed. Please fix this manually or reset the migrations.',
		);
	}


	public function testErrorNewMigrationInTheMiddleOfExistingOnes(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures');
		$migrationA = $this->createMigration($groupA->name, '1s');
		$migrationC = $this->createMigration($groupA->name, '3s');
		$fileA = $this->createFile('1s', $groupA);
		$fileB = $this->createFile('2s', $groupA);
		$fileC = $this->createFile('3s', $groupA);

		// 1s 2s* 3s
		Assert::exception(
			fn() => $resolver->resolve([$migrationC, $migrationA], [$groupA], [$fileA, $fileB, $fileC], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'New migration "structures/2s" must follow after the latest executed migration "structures/3s".',
		);
	}


	public function testErrorNewMigrationInTheMiddleOfExistingOnes2(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('a');
		$groupB = $this->createGroup('b', true, ['a']);
		$groupC = $this->createGroup('c', true, ['b']);

		$migrationA = $this->createMigration($groupA->name, '1a');
		$migrationC = $this->createMigration($groupC->name, '3c');

		$fileA = $this->createFile('1a', $groupA);
		$fileB = $this->createFile('2b', $groupB);
		$fileC = $this->createFile('3c', $groupC);

		// 1a 2b* 3c
		Assert::exception(
			fn() => $resolver->resolve([$migrationA, $migrationC], [$groupA, $groupB, $groupC], [$fileA, $fileB, $fileC], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'New migration "b/2b" must follow after the latest executed migration "c/3c".',
		);
	}


	public function testErrorNewMigrationInTheMiddleOfExistingOnes3(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('a');
		$groupB = $this->createGroup('b', true, ['a']);

		$migrationA = $this->createMigration($groupB->name, '1b');
		$migrationC = $this->createMigration($groupA->name, '3a');

		$fileA = $this->createFile('1b', $groupB);
		$fileB = $this->createFile('2b', $groupB);
		$fileC = $this->createFile('3a', $groupA);

		// 1b 2b* 3a
		Assert::exception(
			fn() => $resolver->resolve([$migrationA, $migrationC], [$groupA, $groupB], [$fileA, $fileB, $fileC], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'New migration "b/2b" must follow after the latest executed migration "a/3a".',
		);
	}


	public function testErrorMigrationDependingOnUnknownGroup(): void
	{
		$resolver = new OrderResolver;

		$migrationA = $this->createMigration('foo', '1s');

		Assert::exception(
			fn() => $resolver->resolve([$migrationA], [], [], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Existing migrations depend on unknown group "foo".',
		);
	}


	public function testErrorGroupDependingOnUnknownGroup(): void
	{
		$resolver = new OrderResolver;

		$groupB = $this->createGroup('data', true, ['structures']);

		Assert::exception(
			fn() => $resolver->resolve([], [$groupB], [], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Group "data" depends on unknown group "structures".',
		);
	}


	public function testErrorDisablingRequiredGroup(): void
	{
		$resolver = new OrderResolver;

		$groupA = $this->createGroup('structures', false);
		$groupB = $this->createGroup('data', true, ['structures']);

		Assert::exception(
			fn() => $resolver->resolve([], [$groupA, $groupB], [], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Group "data" depends on disabled group "structures". Please enable group "structures" to continue.',
		);
	}


	public function testErrorAmbiguousLogicalName(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('structures');
		$groupB = $this->createGroup('data');

		$fileA = $this->createFile('foo', $groupA);
		$fileB = $this->createFile('foo', $groupB);

		Assert::exception(
			fn() => $resolver->resolve([], [$groupA, $groupB], [$fileA, $fileB], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Unable to determine order for migrations "data/foo" and "structures/foo".',
		);
	}


	public function testErrorAmbiguousLogicalNameCyclic(): void
	{
		$resolver = new OrderResolver();

		$groupA = $this->createGroup('structures', true, ['data']);
		$groupB = $this->createGroup('data', true, ['structures']);

		$fileA = $this->createFile('foo', $groupA);
		$fileB = $this->createFile('foo', $groupB);

		Assert::exception(
			fn() => $resolver->resolve([], [$groupA, $groupB], [$fileA, $fileB], Runner::MODE_CONTINUE),
			Nextras\Migrations\LogicException::class,
			'Unable to determine order for migrations "data/foo" and "structures/foo".',
		);
	}


	private function createMigration(string $groupName, string $fileName, ?string $checksum = null, bool $completed = true): Migration
	{
		$migration = new Migration;
		$migration->group = $groupName;
		$migration->filename = $fileName;
		$migration->checksum = $checksum ?? "$fileName.md5";
		$migration->completed = $completed;
		return $migration;
	}


	private function createFile(string $name, Group $group, ?string $checksum = null): File
	{
		$file = new File;
		$file->name = $name;
		$file->group = $group;
		$file->checksum = $checksum ?? "$name.md5";
		return $file;
	}


	/**
	 * @param  list<string> $deps
	 */
	private function createGroup(string $name, bool $enabled = true, array $deps = []): Group
	{
		$group = new Group;
		$group->name = $name;
		$group->enabled = $enabled;
		$group->dependencies = $deps;
		return $group;
	}

}

$test = new OrderResolverTest();
$test->run();
