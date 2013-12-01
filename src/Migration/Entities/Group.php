<?php
namespace Migration\Entities;


class Group
{

	/** @var string */
	public $name;

	/** @var bool */
	public $enabled;

	/** @var string absolute path do directory */
	public $directory;

	/** @var string[] */
	public $dependencies;

}
