<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use DateTime;


/**
 * @author Jan Tvrdík
 */
interface IDbal
{
	/**
	 * @param  string $sql
	 * @return array list of rows represented by assoc. arrays
	 */
	function query($sql);


	/**
	 * @param  string $sql
	 * @return int number of affected rows
	 */
	function exec($sql);


	/**
	 * @param  string $value
	 * @return string escaped string wrapped in quotes
	 */
	function escapeString($value);


	/**
	 * @param  int $value
	 * @return string
	 */
	function escapeInt($value);


	/**
	 * @param  bool $value
	 * @return string
	 */
	function escapeBool($value);


	/**
	 * @param  DateTime $value
	 * @return string
	 */
	function escapeDateTime(DateTime $value);


	/**
	 * @param  string $value
	 * @return string
	 */
	function escapeIdentifier($value);

}
