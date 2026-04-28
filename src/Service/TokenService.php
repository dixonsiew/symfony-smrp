<?php

namespace App\Service;

use App\Entity\User;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class TokenService
{
    private string $secret;

    public function __construct(#[Autowire(env: 'APP_SECRET')] private string $sec)
    {
        $this->secret = $sec;
    }

    public function generateAccessToken(User $user): string
    {
        $key = $this->secret;
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'exp' => time() + (60 * 60 * 720)
        ];
        $token = JWT::encode($payload, $key, 'HS256');
        return $token;
    }

    public function generateRefreshToken(User $user): string
    {
        $key = $this->secret;
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'exp' => time() + (60 * 60 * 87600)
        ];
        $token = JWT::encode($payload, $key, 'HS256');
        return $token;
    }

    public function decodeToken(Request $request): array
    {
        try {
            $key = $this->secret;
            $authHeader = $request->headers->get('Authorization');
            if (null === $authHeader) {
                throw new CustomUserMessageAuthenticationException('No authorization header found.');
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return [$decoded->username, $decoded->user_id];
        } catch (ExpiredException $e) {
            throw new UnauthorizedHttpException('Token has expired', 'Token has expired', code: 401);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }
}