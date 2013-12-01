<?php
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
