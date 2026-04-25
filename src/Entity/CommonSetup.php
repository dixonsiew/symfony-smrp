<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class CommonSetup
{
    #[Groups(['user:read'])]
    public ?int $id;

    #[Groups(['user:read'])]
    public ?string $code;

    #[Groups(['user:read'])]
    public ?string $desc;

    #[Groups(['user:read'])]
    public ?string $ref;

    #[Groups(['user:read'])]
    public ?int $created_by;

    #[Groups(['user:read'])]
    public ?string $created_date;

    #[Groups(['user:read'])]
    public ?int $modified_by;

    #[Groups(['user:read'])]
    public ?string $modified_date;

    #[Groups(['user:read'])]
    public ?bool $deleted;

    #[Groups(['user:read'])]
    public ?int $deleted_by;

    #[Groups(['user:read'])]
    public ?string $deleted_date;

    public static function fromRs($data): self
    {
        $o = new self();
        $o->id = $data['id'];
        $o->code = $data['code'];
        $o->desc = $data['desc'];
        $o->ref = $data['ref'];
        $o->created_by = $data['created_by'];
        $o->created_date = $data['created_date'];
        $o->modified_by = $data['modified_by'];
        $o->modified_date = $data['modified_date'];
        $o->deleted = $data['deleted'];
        $o->deleted_by = $data['deleted_by'];
        $o->deleted_date = $data['deleted_date'];
        return $o;
    }
}