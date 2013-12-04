<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;


abstract class Exception extends \Exception
{
}


abstract class RuntimeException extends Exception
{
}


class LogicException extends Exception
{
}


class ExecutionException extends RuntimeException
{
}


class IOException extends RuntimeException
{
}


class LockException extends RuntimeException
{
}
