<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\DoctrineOrm;

use Doctrine\Common\Cache\ClearableCache;
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

		if ((new \ReflectionMethod($schemaTool, 'getUpdateSchemaSql'))->getNumberOfParameters() > 1) {
			// ORM 2: safe mode never drops tables or sequences
			return $schemaTool->getUpdateSchemaSql($metadata, true);
		}

		// ORM 3 removed safe mode. SchemaTool always introspects tables and sequences present in the target schema
		// and lets the schema assets filter decide about the rest. Rejecting everything hides unmapped assets,
		// so they cannot be dropped.
		$configuration = $this->entityManager->getConnection()->getConfiguration();
		$previousFilter = $configuration->getSchemaAssetsFilter();
		$configuration->setSchemaAssetsFilter(static fn(): bool => false);

		try {
			return $schemaTool->getUpdateSchemaSql($metadata);

		} finally {
			$configuration->setSchemaAssetsFilter($previousFilter);
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
