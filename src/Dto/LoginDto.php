<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public string $password;
}