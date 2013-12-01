<?php
namespace Migration\Entities;

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
