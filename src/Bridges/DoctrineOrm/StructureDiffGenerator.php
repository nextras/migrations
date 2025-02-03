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
	/** @var EntityManagerInterface */
	private $entityManager;

	/** @var string|null absolute path to a file */
	private $ignoredQueriesFile;


	public function __construct(EntityManagerInterface $entityManager, ?string $ignoredQueriesFile = null)
	{
		$this->entityManager = $entityManager;
		$this->ignoredQueriesFile = $ignoredQueriesFile;
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
		$cache = $this->entityManager->getConfiguration()->getMetadataCache();
		if ($cache !== null) {
			$cache->clear();
		}

		$schemaTool = new SchemaTool($this->entityManager);
		$metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
		$queries = $schemaTool->getUpdateSchemaSql($metadata, true);

		return $queries;
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
