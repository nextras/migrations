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


	/**
	 * @param IDbal  $dbal
	 * @param string $tableName
	 */
	public function __construct(IDbal $dbal, $tableName = 'migrations')
	{
		$this->dbal = $dbal;
		$this->tableName = $dbal->escapeIdentifier($tableName);
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
		$query = @file_get_contents($path);
		if ($query === FALSE) {
			throw new IOException("Cannot open file '$path'.");
		}

		$delimiter = ';';
		$offset = $queries = 0;
		$space = "(?:\\s|/\\*.*\\*/|(?:#|-- )[^\\n]*\\n|--\\n)";

		if ($this instanceof PgSqlDriver) {
			$parse = '[\'"]|/\*|-- |$|\$[^$]*\$';
		} else {
			$parse = '[\'"`#]|/\*|-- |$';
		}

		while ($query != '') {
			if (!$offset && preg_match("~^{$space}*DELIMITER\\s+(\\S+)~i", $query, $match)) {
				$delimiter = $match[1];
				$query = substr($query, strlen($match[0]));
			} else {
				preg_match('(' . preg_quote($delimiter) . "\\s*|$parse)", $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
				$found = $match[0][0];
				$offset = $match[0][1] + strlen($found);

				if (!$found && rtrim($query) === '') {
					break;
				}

				if (!$found || rtrim($found) == $delimiter) { // end of a query
					$q = substr($query, 0, $match[0][1]);

					$queries++;
					$this->dbal->exec($q);

					$query = substr($query, $offset);
					$offset = 0;
				} else { // find matching quote or comment end
					while (preg_match('(' . ($found == '/*' ? '\*/' : ($found == '[' ? ']' : (preg_match('~^-- |^#~', $found) ? "\n" : preg_quote($found) . "|\\\\."))) . '|$)s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
						$s = $match[0][0];
						$offset = $match[0][1] + strlen($s);
						if ($s[0] !== '\\') {
							break;
						}
					}
				}
			}
		}

		return $queries;
	}

}
