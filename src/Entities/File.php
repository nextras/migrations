<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Entities;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class File
{
	public Group $group;

	public string $extension;

	/** @var string logical name, may or may not correspond to filename */
	public string $name;

	/** @var string absolute path */
	public string $path;

	public string $checksum;
}
