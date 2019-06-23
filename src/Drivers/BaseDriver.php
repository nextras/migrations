<?php

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


/**
 * @author Jan Skrasek
 * @author Petr Prochazka
 * @author Jan Tvrdik
 */
abstract class BaseDriver implements IDriver
{
	/** @var IDbal */
	protected $dbal;

	/** @var string */
	protected $tableName;

	/** @var NULL|string */
	protected $tableNameQuoted;


	/**
	 * @param IDbal  $dbal
	 * @param string $tableName
	 */
	public function __construct(IDbal $dbal, $tableName = 'migrations')
	{
		$this->dbal = $dbal;
		$this->tableName = $tableName;
	}


	public function setupConnection()
	{
		$this->tableNameQuoted = $this->dbal->escapeIdentifier($this->tableName);
	}


	/**
	 * Loads and executes SQL queries from given file. Taken from Adminer (Apache License), modified.
	 *
	 * @author   Jakub Vrána
	 * @author   Jan Tvrdík
	 * @author   Michael Moravec
	 * @author   Jan Skrasek
	 * @license  Apache License
	 *
	 * @param  string $path
	 * @return int number of executed queries
	 */
	public function loadFile($path)
	{
		$content = @file_get_contents($path);
		if ($content === FALSE) {
			throw new IOException("Cannot open file '$path'.");
		}

		$queryOffset = 0;
		$parseOffset = 0;
		$queries = 0;

		$space = "(?:\\s|/\\*.*\\*/|(?:#|-- )[^\\n]*(?:\\n|\\z)|--(?:\\n|\\z))";
		$spacesRe = "~\\G{$space}*\\z~";
		$delimiter = ';';
		$delimiterRe = "~\\G{$space}*DELIMITER\\s+(\\S+)~i";

		$openRe = $this instanceof PgSqlDriver ? '[\'"]|/\*|-- |\z|\$[^$]*\$' : '[\'"`#]|/\*|-- |\z';
		$parseRe = "(;|$openRe)";
		$endReTable = [
			'\'' => '(\'|\\\\.|\z)s',
			'"' => '("|\\\\.|\z)s',
			'/*' => '(\*/|\z)',
			'[' => '(]|\z)',
		];

		while (TRUE) {
			if (preg_match($delimiterRe, $content, $match, 0, $queryOffset)) {
				$delimiter = $match[1];
				$queryOffset += strlen($match[0]);
				$parseOffset += strlen($match[0]);
				$parseRe = '(' . preg_quote($delimiter) . "|$openRe)";
			}

			while (TRUE) {
				preg_match($parseRe, $content, $match, PREG_OFFSET_CAPTURE, $parseOffset); // should always match
				$found = $match[0][0];
				$parseOffset = $match[0][1] + strlen($found);

				if ($found === $delimiter) { // delimited query
					$queryLength = $match[0][1] - $queryOffset;
					break;

				} elseif ($found) { // find matching quote or comment end
					$endRe = isset($endReTable[$found]) ? $endReTable[$found] : '(' . (preg_match('~^-- |^#~', $found) ? "\n" : preg_quote($found) . "|\\\\.") . '|\z)s';
					while (preg_match($endRe, $content, $match, PREG_OFFSET_CAPTURE, $parseOffset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
						$s = $match[0][0];
						if (strlen($s) === 0) {
							break 3;
						}

						$parseOffset = $match[0][1] + strlen($s);
						if ($s[0] !== '\\') {
							continue 2;
						}
					}

				} else { // last query or EOF
					if (preg_match($spacesRe, $content, $_, 0, $queryOffset)) {
						break 2;

					} else {
						$queryLength = $match[0][1] - $queryOffset;
						break;
					}
				}
			}

			$q = substr($content, $queryOffset, $queryLength);

			$queries++;
			$this->dbal->exec($q);
			$queryOffset = $parseOffset;
		}

		return $queries;
	}

}
