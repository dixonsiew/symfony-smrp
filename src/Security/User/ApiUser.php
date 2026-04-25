<?php

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class ApiUser implements UserInterface, EquatableInterface
{
    private string $userIdentifier;
    private array $roles;
    private array $userData; // Custom additional data

    public function __construct(string $userIdentifier, array $roles, array $userData = [])
    {
        $this->userIdentifier = $userIdentifier;
        $this->roles = $roles;
        $this->userData = $userData;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->userIdentifier === $user->getUserIdentifier();
    }

    // Custom getters for your additional data
    public function getUserData(): array
    {
        return $this->userData;
    }
}
