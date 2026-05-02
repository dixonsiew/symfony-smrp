<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;

use App\Dto\LoginDto;
use App\Service\UserService;
use App\Service\TokenService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Security;

class AuthController extends AbstractController
{
    private UserService $userService;
    private TokenService $tokenService;
    private LoggerInterface $logger;

    public function __construct(UserService $userService, TokenService $tokenService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    private function handleError(\Exception $e)
    {
        if ($e instanceof UnauthorizedHttpException || 
            $e instanceof NotFoundHttpException) {
            throw $e;
        }
        
        $this->logger->error($e->getMessage());
        throw $e;
    }

    #[Route('/o/token', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Successful response')]
    #[OA\Tag(name: 'Auth')]
    public function login(#[MapRequestPayload] LoginDto $data): JsonResponse
    {
        try {
            $user = $this->userService->findByUsername($data->username);
            if ($user === null) {
                throw new UnauthorizedHttpException('Invalid Credentials', 'Invalid Credentials', code: 401);
            }

            $valid = $this->userService->validateCredentials($user, $data->password);
            if (!$valid) {
                throw new UnauthorizedHttpException('Invalid Credentials', 'Invalid Credentials', code: 401);
            }

            $this->userService->updateLastLogin($user->id);
            $token = $this->tokenService->generateAccessToken($user);
            $refreshToken = $this->tokenService->generateRefreshToken($user);
            return $this->json([
                'type' => 'bearer',
                'token' => $token,
                'refresh_token' => $refreshToken
            ]);

        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/current-user', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Successful response')]
    #[OA\Tag(name: 'Auth')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function userDetails(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new NotFoundHttpException('User not found', code: 404);
            }

            $o = $user->getUser();
            return $this->json([
                'id' => $o->id,
                'username'=> $o->username,
                'first_name' => $o->first_name,
                'last_name' => $o->last_name,
                'roles' => $o->roles
            ]);

        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
}