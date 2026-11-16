<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras;
use Nextras\Migrations\Drivers\SqliteDriver;
use Nextras\Migrations\LockException;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


class SqliteDriverTest extends Tester\TestCase
{
	public function testLockIsExclusivePerDatabaseFile(): void
	{
		$file = TEMP_DIR . '/lock.sqlite';
		$first = $this->createDriver($file);
		$second = $this->createDriver($file);

		$first->lock();
		Assert::true(is_file($file . '.lock'));
		Assert::exception(fn() => $second->lock(), LockException::class, 'Unable to acquire a lock.');

		$first->unlock();
		$second->lock();
		$second->unlock();
	}


	public function testLockIsReleasedWhenHandleIsClosed(): void
	{
		$file = TEMP_DIR . '/closed.sqlite';
		$first = $this->createDriver($file);
		$first->lock();
		unset($first); // a crashed process closes its handles too

		$second = $this->createDriver($file);
		$second->lock();
		$second->unlock();
		Assert::true(true);
	}


	public function testInMemoryDatabaseIsNotLocked(): void
	{
		$driver = $this->createDriver('');
		$driver->lock();
		$driver->lock();
		$driver->unlock();
		Assert::false(is_file('.lock'));
	}


	public function testUnlockWithoutLockIsNoop(): void
	{
		$this->createDriver(TEMP_DIR . '/unused.sqlite')->unlock();
		Assert::false(is_file(TEMP_DIR . '/unused.sqlite.lock'));
	}


	protected function tearDown(): void
	{
		Mockery::close();
	}


	private function createDriver(string $file): SqliteDriver
	{
		$dbal = Mockery::mock(Nextras\Migrations\IDbal::class);
		$dbal->shouldReceive('query')->with('PRAGMA database_list')->andReturn([
			['seq' => 0, 'name' => 'main', 'file' => $file],
		]);

		return new class($dbal) extends SqliteDriver {
			protected const LOCK_TIMEOUT = 0;
		};
	}
}


(new SqliteDriverTest)->run();
