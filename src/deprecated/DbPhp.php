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
		trigger_error(sprintf('Class %s is deprecated, use class PhpHandler instead.', __CLASS__), E_USER_DEPRECATED);
		parent::__construct(['db' => $context]);
	}


	public function getName()
	{
		return 'db.php';
	}

}
