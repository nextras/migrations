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
		parent::__construct($params, 'simple.php');
	}
}
