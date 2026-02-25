<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\DoctrineDbal;

use DateTimeInterface;
use Doctrine;
use Nextras\Migrations\IDbal;


class DoctrineAdapter implements IDbal
{
	public function __construct(
		private Doctrine\DBAL\Connection $conn,
	)
	{
	}


	public function query(string $sql): array
	{
		return $this->conn->fetchAllAssociative($sql);
	}


	public function exec(string $sql): int
	{
		return $this->conn->executeStatement($sql);
	}


	public function escapeString(string $value): string
	{
		return $this->conn->getDatabasePlatform()->quoteStringLiteral($value);
	}


	public function escapeInt(int $value): string
	{
		return (string) $value;
	}


	public function escapeBool(bool $value): string
	{
		return $this->escapeString((string) (int) $value);
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		return $this->escapeString($value->format($this->conn->getDatabasePlatform()->getDateTimeFormatString()));
	}


	public function escapeIdentifier(string $value): string
	{
		return $this->conn->getDatabasePlatform()->quoteIdentifier($value);
	}
}
