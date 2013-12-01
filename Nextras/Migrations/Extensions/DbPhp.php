<?php
namespace Nextras\Migrations\Extensions;

use Nette\Database\Context;


class DbPhp extends SimplePhp
{

	public function __construct(Context $context)
	{
		parent::__construct(array('db' => $context));
	}

	public function getName()
	{
		return 'db.php';
	}

}
