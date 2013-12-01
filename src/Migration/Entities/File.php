<?php
namespace Migration\Entities;

use DateTime;


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
