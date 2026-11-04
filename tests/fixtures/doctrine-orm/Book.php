<?php declare(strict_types = 1);

namespace NextrasTests\Migrations\DoctrineOrm;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'book')]
class Book
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer')]
	public int $id;

	#[ORM\ManyToOne(targetEntity: Author::class)]
	public ?Author $author = null;
}
