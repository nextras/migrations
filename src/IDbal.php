<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use DateTimeInterface;


/**
 * @author Jan Tvrdík
 */
interface IDbal
{
	/**
	 * @return array<int, array<string, mixed>> list of rows represented by assoc. arrays
	 */
	public function query(string $sql): array;


	/**
	 * @return int number of affected rows
	 */
	public function exec(string $sql): int;


	public function escapeString(string $value): string;


	public function escapeInt(int $value): string;


	public function escapeBool(bool $value): string;


	public function escapeDateTime(DateTimeInterface $value): string;


	public function escapeIdentifier(string $value): string;
}
