<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\DoctrineOrm;

use Doctrine\Common\Cache\ClearableCache;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\NamedObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nextras;
use Nextras\Migrations\IDiffGenerator;


class StructureDiffGenerator implements IDiffGenerator
{
	/**
	 * @param string|null $ignoredQueriesFile absolute path to a file
	 */
	public function __construct(
		private EntityManagerInterface $entityManager,
		private ?string $ignoredQueriesFile = null,
	)
	{
	}


	public function getExtension(): string
	{
		return 'sql';
	}


	public function generateContent(): string
	{
		$queries = array_diff($this->getUpdateQueries(), $this->getIgnoredQueries());
		$content = $queries ? (implode(";\n", $queries) . ";\n") : '';

		return $content;
	}


	/**
	 * @return list<string>
	 */
	protected function getUpdateQueries(): array
	{
		$this->entityManager->getConfiguration()->getMetadataCache()?->clear();

		$schemaTool = new SchemaTool($this->entityManager);
		$metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
		// ORM 2 has native safe mode; ORM 3 removed the second parameter.
		if ((new \ReflectionMethod(SchemaTool::class, 'getUpdateSchemaSql'))->getNumberOfParameters() > 1) {
			return $schemaTool->getUpdateSchemaSql($metadata, true);
		}

		$schema = $schemaTool->getSchemaFromMetadata($metadata);
		$config = $this->entityManager->getConnection()->getConfiguration();
		$previousFilter = $config->getSchemaAssetsFilter();
		$config->setSchemaAssetsFilter(static function ($asset) use ($schema, $previousFilter): bool {
			// DBAL passes table names as strings and sequences as schema assets.
			$name = $asset instanceof NamedObject ? $asset->getObjectName()->toString()
				: ($asset instanceof AbstractAsset ? $asset->getName() : $asset);

			return ($previousFilter === null || $previousFilter($asset))
				&& ($schema->hasTable($name) || $schema->hasSequence($name));
		});

		try {
			return $schemaTool->getUpdateSchemaSql($metadata);
		} finally {
			$config->setSchemaAssetsFilter($previousFilter);
		}
	}


	/**
	 * @return list<string>
	 */
	protected function getIgnoredQueries(): array
	{
		if ($this->ignoredQueriesFile === null) {
			return [];
		}

		$content = file_get_contents($this->ignoredQueriesFile);
		$queries = preg_split('~(\s*;\s*\r?\n|\z)~', $content, -1, PREG_SPLIT_NO_EMPTY);

		return $queries;
	}
}
