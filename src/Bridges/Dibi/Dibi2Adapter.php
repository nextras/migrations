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


class Dibi2Adapter implements IDbal
{
	/** @var DibiConnection */
	private $conn;


	public function __construct(DibiConnection $dibi)
	{
		$this->conn = $dibi;
	}


	public function query($sql)
	{
		$result = $this->conn->nativeQuery($sql);
		$result->setRowClass(NULL);
		return $result->fetchAll();
	}


	public function exec($sql)
	{
		return $this->conn->nativeQuery($sql);
	}


	public function escapeString($value)
	{
		return $this->conn->getDriver()->escape($value, dibi::TEXT);
	}


	public function escapeInt($value)
	{
		return (string) (int) $value;
	}


	public function escapeBool($value)
	{
		return $this->conn->getDriver()->escape($value, dibi::BOOL);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->getDriver()->escape($value, dibi::DATETIME);
	}


	public function escapeIdentifier($value)
	{
		return $this->conn->getDriver()->escape($value, dibi::IDENTIFIER);
	}

}
