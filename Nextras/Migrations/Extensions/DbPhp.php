<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

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
