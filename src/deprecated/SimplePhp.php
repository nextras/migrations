<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

/**
 * @deprecated
 */
class SimplePhp extends PhpHandler
{
	public function __construct(array $params = [])
	{
		trigger_error(sprintf('Class %s is deprecated, use class PhpHandler instead.', __CLASS__), E_USER_DEPRECATED);
		parent::__construct($params);
	}


	public function getName()
	{
		return 'simple.php';
	}

}
