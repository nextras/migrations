<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

use Nette\Database\Context;
use Nette\Database\Drivers\MsSqlDriver;
use Nette\Database\Drivers\PgSqlDriver;
use Nette\Database\Drivers\SqliteDriver;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IOException;
use Nextras\Migrations\LogicException;


/**
 * @author Jan Skrasek
 * @author Petr Procházka
 */
class NetteDbSql implements IExtensionHandler
{
	/** @var Context */
	private $context;


	public function __construct(Context $context)
	{
		$this->context = $context;
	}


	/**
	 * Unique extension name.
	 * @return string
	 */
	public function getName()
	{
		return 'sql';
	}


	/**
	 * @param  File
	 * @return int number of queries
	 */
	public function execute(File $sql)
	{
		$count = $this->loadFile($sql->getPath());
		if ($count === 0) {
			throw new LogicException("{$sql->file} neobsahuje zadne sql.");
		}
		return $count;
	}


	/**
	 * Import taken from Adminer, slightly modified
	 *
	 * @param    string path to imported file
	 * @param    DibiConnection
	 * @returns  int number of executed queries
	 *
	 * @author   Jakub Vrána
	 * @author   Jan Tvrdík
	 * @author   Michael Moravec
	 * @author   Jan Skrasek
	 * @license  Apache License
	 */
	protected function loadFile($file)
	{
		$query = @file_get_contents($file);
		if (!$query) {
			throw new IOException("Cannot open file '$file'.");
		}

		$delimiter = ';';
		$offset = $queries = 0;
		$space = "(?:\\s|/\\*.*\\*/|(?:#|-- )[^\\n]*\\n|--\\n)";

		$driver = $this->context->getConnection()->getSupplementalDriver();
		if ($driver instanceof MsSqlDriver) {
			$parse = '[\'"[]|/\*|-- |$';
		} elseif ($driver instanceof SqliteDriver) {
			$parse = '[\'"`[]|/\*|-- |$';
		} elseif ($driver instanceof PgSqlDriver) {
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
					$this->context->query($q);

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
