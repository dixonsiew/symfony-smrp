<?php

namespace App\Security;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') &&
            str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authHeader = $request->headers->get('Authorization');
        if (null === $authHeader) {
            throw new CustomUserMessageAuthenticationException('No authorization header found.');
        }

        $token = str_replace('Bearer ', '', $authHeader);
        try {
            $key = $_ENV['APP_SECRET'];
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $username = $decoded->username;
            $roles = ['ROLE_USER'];
            $badge = new UserBadge($username);
            return new SelfValidatingPassport($badge);
        } catch (ExpiredException $e) {
            throw new CustomUserMessageAuthenticationException('Token has expired', [], 401);
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage(), [], 500);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'statusCode' => 401,
            'message' => 'Authentication Failed',
        ], 401);
    }
}
