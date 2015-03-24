<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\Dibi;

use DateTime;
use dibi;
use DibiConnection;
use Nextras\Migrations\IDbal;


class DibiAdapter implements IDbal
{
	/** @var DibiConnection */
	private $dibi;


	public function __construct(DibiConnection $dibi)
	{
		$this->dibi = $dibi;
	}


	public function query($sql)
	{
		$result = $this->dibi->nativeQuery($sql);
		$result->setRowFactory(function (array $row) { return $row; });
		return $result->fetchAll();
	}


	public function exec($sql)
	{
		return $this->dibi->nativeQuery($sql);
	}


	public function escapeString($value)
	{
		return $this->dibi->getDriver()->escape($value, dibi::TEXT);
	}


	public function escapeInt($value)
	{
		return (int) $value;
	}


	public function escapeBool($value)
	{
		return $this->dibi->getDriver()->escape($value, dibi::BOOL);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->dibi->getDriver()->escape($value, dibi::DATETIME);
	}


	public function escapeIdentifier($value)
	{
		return $this->dibi->getDriver()->escape($value, dibi::IDENTIFIER);
	}

}
