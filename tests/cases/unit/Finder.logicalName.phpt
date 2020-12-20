<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Mockery;
use Nextras;
use Nextras\Migrations\Engine\Finder;
use Nextras\Migrations\Entities\Group;
use Tester;
use Tester\Assert;


require __DIR__ . '/../../bootstrap.php';


class FinderLogicalNameTest extends Tester\TestCase
{
	/** @var Finder|Mockery\MockInterface */
	private $finder;

	/** @var Group[] */
	private $groups;


	protected function setUp()
	{
		parent::setUp();
		$this->finder = Mockery::mock(Nextras\Migrations\Engine\Finder::class)
			->shouldAllowMockingProtectedMethods()
			->shouldDeferMissing()
			->shouldReceive('getChecksum')
			->getMock();

		$group = new Group();
		$group->dependencies = [];
		$group->directory = './baseDir/structures';
		$group->enabled = true;
		$group->name = 'structures';
		$this->groups = [$group];
	}


	public function testSimple()
	{
		$this->finder->shouldReceive('getItems')
			->with('./baseDir/structures')
			->andReturn([
				'2015-03-04.sql',
				'2015-03-06.sql',
				'2015-07-06.sql',
			]);

		$files = $this->finder->find($this->groups, ['sql']);
		Assert::count(3, $files);
		Assert::same('2015-03-04.sql', $files[0]->name);
		Assert::same('2015-03-06.sql', $files[1]->name);
		Assert::same('2015-07-06.sql', $files[2]->name);
	}


	public function testComplex()
	{
		$this->finder->shouldReceive('getItems')
			->with('./baseDir/structures')
			->andReturn(['2015', '2015-07-06.sql']);

		$this->finder->shouldReceive('getItems')
			->with('./baseDir/structures/2015')
			->andReturn(['03', '03-06.sql']);

		$this->finder->shouldReceive('getItems')
			->with('./baseDir/structures/2015/03')
			->andReturn(['2015-03-04.sql']);

		$files = $this->finder->find($this->groups, ['sql']);
		Assert::count(3, $files);
		Assert::same('2015-07-06.sql', $files[0]->name);
		Assert::same('2015-03-06.sql', $files[1]->name);
		Assert::same('2015-03-04.sql', $files[2]->name);
	}
}


$test = new FinderLogicalNameTest();
$test->run();
