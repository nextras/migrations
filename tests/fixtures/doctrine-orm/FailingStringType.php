<?php declare(strict_types = 1);

namespace NextrasTests\Migrations\DoctrineOrm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;


class FailingStringType extends StringType
{
	public static $onDeclaration;


	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
	{
		(self::$onDeclaration)();
		return parent::getSQLDeclaration($column, $platform);
	}
}
