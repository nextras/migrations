<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Entities;

use DateTime;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class Migration
{
	public int $id;

	public string $group;

	public string $filename;

	public string $checksum;

	public DateTime $executedAt;

	public bool $completed;
}
