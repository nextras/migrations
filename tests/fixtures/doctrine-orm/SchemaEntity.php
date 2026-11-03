<?php declare(strict_types = 1);

namespace NextrasTests\Migrations\DoctrineOrm;

use Doctrine\ORM\Mapping\ClassMetadata;


class SchemaEntity
{
	public static $schema;
	public $id;
	public $label;
	public $added;


	public static function loadMetadata(ClassMetadata $metadata): void
	{
		$metadata->setPrimaryTable(['name' => 'managed', 'schema' => self::$schema]);
		$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
		$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
		$metadata->setSequenceGeneratorDefinition([
			'sequenceName' => self::$schema . '.managed_id_seq',
			'allocationSize' => 1,
			'initialValue' => 1,
		]);
		$metadata->mapField(['fieldName' => 'label', 'type' => 'string', 'length' => 60]);
		$metadata->mapField(['fieldName' => 'added', 'type' => 'boolean', 'nullable' => true]);
	}
}
