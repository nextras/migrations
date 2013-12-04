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

	/** @var string */
	public $name;

	/** @var string */
	public $checksum;


	public function getPath()
	{
		return $this->group->directory . '/' . $this->name;
	}

}
