<?php

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
	/** @var Group */
	public $group;

	/** @var string */
	public $extension;

	/** @var string logical name, may or may not correspond to filename */
	public $name;

	/** @var string absolute path */
	public $path;

	/** @var string */
	public $checksum;

}
