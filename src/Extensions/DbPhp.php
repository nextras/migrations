<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

use Nette;


/**
 * @deprecated
 */
class DbPhp extends PhpHandler
{
	public function __construct(Nette\Database\Context $context)
	{
		parent::__construct(['db' => $context], 'db.php');
	}
}
