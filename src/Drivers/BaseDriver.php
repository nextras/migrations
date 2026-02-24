<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nextras\Migrations\IDbal;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IOException;
use Nextras\MultiQueryParser\IMultiQueryParser;


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
abstract class BaseDriver implements IDriver
{
	protected ?string $tableNameQuoted = null;


	public function __construct(
		protected IDbal $dbal,
		protected string $tableName = 'migrations',
	)
	{
	}


	public function setupConnection(): void
	{
		$this->tableNameQuoted = $this->dbal->escapeIdentifier($this->tableName);
	}


	abstract protected function createMultiQueryParser(): IMultiQueryParser;


	public function loadFile(string $path): int
	{
		$parser = $this->createMultiQueryParser();

		try {
			$queries = 0;
			foreach ($parser->parseFile($path) as $query) {
				$this->dbal->exec($query);
				$queries++;
			}
			return $queries;

		} catch (\Nextras\MultiQueryParser\Exception\RuntimeException $e) {
			throw new IOException($e->getMessage(), 0, $e);
		}
	}
}
