<?php
namespace Nextras\Migrations\Extensions;

use Nette\Database\Context;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IOException;
use Nextras\Migrations\LogicException;


/**
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
		if ($count === 0)
		{
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
	 * @license  Apache License
	 */
	protected function loadFile($file)
	{
		$query = @file_get_contents($file);
		if (!$query) throw new IOException("Cannot open file '$file'.");

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
					$this->context->query($q);

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
