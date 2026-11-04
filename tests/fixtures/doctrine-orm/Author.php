<?php declare(strict_types = 1);

namespace NextrasTests\Migrations\DoctrineOrm;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'author')]
class Author
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer')]
	public int $id;

	#[ORM\Column(type: 'string', length: 100)]
	public string $name;
}
