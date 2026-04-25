<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public string $first_name;

    #[Assert\Length(max: 150)]
    #[Assert\Type('string')]
    public ?string $last_name = null;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $role_id;
}