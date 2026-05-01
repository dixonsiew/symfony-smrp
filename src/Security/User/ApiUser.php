<?php

namespace App\Security\User;

use App\Entity\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class ApiUser implements UserInterface, EquatableInterface
{
    private int $userId;
    private string $userIdentifier;
    private array $roles;
    private array $userData; // Custom additional data
    private User $user;

    public function __construct(int $userId, string $userIdentifier, array $roles, array $userData = [], User $user)
    {
        $this->userId = $userId;
        $this->userIdentifier = $userIdentifier;
        $this->roles = $roles;
        $this->userData = $userData;
        $this->user = $user;
    }

    public function getUserId(): int
    {
        return $this->userId;
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
    
    public function getUser(): User
    {
        return $this->user;
    }
}
