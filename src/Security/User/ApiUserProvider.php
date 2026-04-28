<?php

namespace App\Security\User;

use App\Service\UserService;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ApiUserProvider implements UserProviderInterface
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userService->findByUsername($identifier);
        // Create and return User object
        return new ApiUser(
            $user->username,
            ['ROLE_USER'],
            []
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // if (!$user instanceof ApiUser) {
        //     throw new UnsupportedUserException('Unsupported user class');
        // }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return ApiUser::class === $class || is_subclass_of($class, ApiUser::class);
    }
}