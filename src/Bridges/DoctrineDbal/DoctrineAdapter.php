<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\DoctrineDbal;

use DateTime;
use Doctrine;
use Nextras\Migrations\IDbal;


class DoctrineAdapter implements IDbal
{
	/** @var Doctrine\DBAL\Connection */
	private $conn;


	public function __construct(Doctrine\DBAL\Connection $conn)
	{
		$this->conn = $conn;
	}


	public function query($sql)
	{
		return method_exists($this->conn, 'fetchAllAssociative')
			? $this->conn->fetchAllAssociative($sql)
			: $this->conn->fetchAll($sql);
	}


	public function exec($sql)
	{
		return method_exists($this->conn, 'executeStatement')
			? $this->conn->executeStatement($sql)
			: $this->conn->exec($sql);
	}


	public function escapeString($value)
	{
		return $this->conn->quote($value, 'string');
	}


	public function escapeInt($value)
	{
		return $this->conn->quote($value, 'integer');
	}


	public function escapeBool($value)
	{
		return $this->conn->quote($value, 'boolean');
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->quote($value, 'datetime');
	}


	public function escapeIdentifier($value)
	{
		return $this->conn->quoteIdentifier($value);
	}

}
