<?php
namespace Migration\Extensions;

use DibiConnection;


class DibiPhp extends SimplePhp
{

	public function __construct(DibiConnection $dibi)
	{
		parent::__construct(array('dibi' => $dibi));
	}

	public function getName()
	{
		return 'dibi.php';
	}

}
