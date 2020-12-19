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
	/** @var int */
	public $id;

	/** @var string */
	public $group;

	/** @var string */
	public $filename;

	/** @var string */
	public $checksum;

	/** @var DateTime */
	public $executedAt;

	/** @var bool */
	public $completed;
}
