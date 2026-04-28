<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CommonSetupDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    public string $code;

    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    public string $desc;

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public string $ref;
}