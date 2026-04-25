<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class KeywordDto
{
    #[Groups(['user:read'])]
    public string $keyword;
}