<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class Role
{
    #[Groups(['user:read'])]
    public ?int $id;

    #[Groups(['user:read'])]
    public ?string $name;
}