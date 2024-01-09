<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\Dibi;

use DateTimeInterface;
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


	public function query(string $sql): array
	{
		$result = $this->conn->nativeQuery($sql);
		$result->setRowClass(null);
		return $result->fetchAll();
	}


	public function exec(string $sql): int
	{
		$this->conn->nativeQuery($sql);
		return $this->conn->getAffectedRows();
	}


	public function escapeString(string $value): string
	{
		return $this->conn->getDriver()->escapeText($value);
	}


	public function escapeInt(int $value): string
	{
		return (string) $value;
	}


	public function escapeBool(bool $value): string
	{
		return (string) $this->conn->getDriver()->escapeBool($value);
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		return $this->conn->getDriver()->escapeDateTime($value);
	}


	public function escapeIdentifier(string $value): string
	{
		return $this->conn->getDriver()->escapeIdentifier($value);
	}

}
