<?php
namespace Migration\Extensions;

use DibiConnection;
use Migration;
use RuntimeException;


/**
 * @author Petr Procházka
 */
class Sql implements Migration\IExtensionHandler
{

	/** @var DibiConnection */
	private $dibi;

	/**
	 * @param DibiConnection
	 */
	public function __construct(DibiConnection $dibi)
	{
		$this->dibi = $dibi;
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
	 * @param Migration\Entities\File
	 * @return int number of queries
	 */
	public function execute(Migration\Entities\File $sql)
	{
		$count = $this->loadFile($sql->getPath());
		if ($count === 0)
		{
			throw new Migration\Exception("{$sql->file} neobsahuje zadne sql.");
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
	 * @author   Jakub Vrána, Jan Tvrdík, Michael Moravec
	 * @license  Apache License
	 */
	protected function loadFile($file)
	{
		$query = @file_get_contents($file);
		if (!$query) throw new RuntimeException("Cannot open file '$file'.");

		$delimiter = ';';
		$offset = 0;
		$queries = 0;

		while ($query != '') {
			if (!$offset && preg_match('~^\\s*DELIMITER\\s+(.+)~i', $query, $match)) {
				$delimiter = $match[1];
				$query = substr($query, strlen($match[0]));

			} else {
				preg_match('(' . preg_quote($delimiter) . '|[\'`"]|/\\*|-- |#|$)', $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
				$found = $match[0][0];
				$offset = $match[0][1] + strlen($found);

				if (!$found && rtrim($query) === '') {
					break;
				}

				if (!$found || $found == $delimiter) { // end of a query
					$q = substr($query, 0, $match[0][1]);

					$queries++;
					$this->dibi->nativeQuery($q);

					$query = substr($query, $offset);
					$offset = 0;

				} else { // find matching quote or comment end
					while (preg_match('~' . ($found == '/*' ? '\\*/' : (preg_match('~-- |#~', $found) ? "\n" : "$found|\\\\.")) . '|$~s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
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
