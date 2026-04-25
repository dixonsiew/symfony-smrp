<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $key = '*frxj2hym#7s8wp7k(jlb9b#s6kwy90o)c%#(*gigkrw+*qtz';
        $decoded = JWT::decode($accessToken, new Key($key, 'HS256'));
        $username = $decoded->email;
        if (empty($username)) {
            throw new BadCredentialsException('Invalid credentials.', 401);
            // and return a UserBadge object containing the user identifier from the found token
            // (this is the same identifier used in Security configuration; it can be an email,
            // a UUID, a username, a database ID, etc.)
        }

        return new UserBadge($username);
    }
}
