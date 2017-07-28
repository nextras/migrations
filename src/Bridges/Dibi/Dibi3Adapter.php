<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\Dibi;

use DateTime;
use Dibi;
use Nextras\Migrations\IDbal;


class Dibi3Adapter implements IDbal
{
	/** @var Dibi\Connection */
	protected $conn;


	public function __construct(Dibi\Connection $dibi)
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
		return $this->conn->getDriver()->escapeText($value);
	}


	public function escapeInt($value)
	{
		return (string) (int) $value;
	}


	public function escapeBool($value)
	{
		return $this->conn->getDriver()->escapeBool($value);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->getDriver()->escapeDateTime($value);
	}


	public function escapeIdentifier($value)
	{
		return $this->conn->getDriver()->escapeIdentifier($value);
	}

}
