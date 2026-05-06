<?php

namespace App\Controller\Setup;

use Psr\Log\LoggerInterface;

use App\Constants\Constants;
use App\Dto\KeywordDto;
use App\Dto\UserDto;
use App\Model\Pager;
use App\Entity\User;
use App\Service\RoleService;
use App\Service\UserService;
use App\Service\HelperService;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

use OpenApi\Attributes as OA;

use function strlen;

#[Route('/api')]
class UserController extends AbstractController
{
    private RoleService $roleService;
    private UserService $userService;
    private HelperService $helperService;
    private LoggerInterface $logger;

    public function __construct(UserService $userService, RoleService $roleService, HelperService $helperService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->helperService = $helperService;
        $this->logger = $logger;
    }

    private function handleError(\Exception $e)
    {
        if ($e instanceof UnauthorizedHttpException || 
            $e instanceof NotFoundHttpException || 
            $e instanceof BadRequestException) {
            throw $e;
        }
        
        $this->logger->error($e->getMessage());
        throw $e;
    }

    #[Route('/users', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/User')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['user:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(#[MapQueryParameter] int $_page = 1, #[MapQueryParameter] int $_limit = 20, #[MapQueryParameter] string $sort = ''): JsonResponse
    {
        $sortBy = 'username';
        $sortDir = 'asc';
        if (strlen($sort) > 0) {
            [$sortBy, $sortDir] = $this->helperService->getSort($sort, $sortBy, $sortDir);
        }

        try {
            $total = $this->userService->count();
            $pg = new Pager($total, $_page, $_limit);
            $lx = $this->userService->findAll($pg->getLowerBound(), $pg->pageSize, $sortBy, $sortDir);
            $res = new JsonResponse($lx);
            $res->headers->set(Constants::X_TOTAL_COUNT, "$total");
            $res->headers->set(Constants::X_TOTAL_PAGE, "{$pg->getTotalPages()}");
            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/users', methods: ['POST'])]
    #[OA\RequestBody(description: 'Keyword', required: false, content: new OA\JsonContent(ref: '#/components/schemas/KeywordDto'))]
    #[OA\Tag(name: 'Setup/User')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['user:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchList(
        #[MapRequestPayload] KeywordDto $data,
        #[MapQueryParameter] int $_page = 1,
        #[MapQueryParameter] int $_limit = 20,
        #[MapQueryParameter] string $sort = ''
    ): JsonResponse {
        $sortBy = 'username';
        $sortDir = 'asc';
        $key = '%' . $data->keyword . '%';

        if (strlen($sort) > 0) {
            [$sortBy, $sortDir] = $this->helperService->getSort($sort, $sortBy, $sortDir);
        }

        try {
            $total = $this->userService->countByKeyword($key);
            $pg = new Pager($total, $_page, $_limit);
            $lx = $this->userService->findByKeyword($key, $pg->getLowerBound(), $pg->pageSize, $sortBy, $sortDir);
            $res = new JsonResponse($lx);
            $res->headers->set(Constants::X_TOTAL_COUNT, "$total");
            $res->headers->set(Constants::X_TOTAL_PAGE, "{$pg->getTotalPages()}");
            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/user', methods: ['POST'])]
    #[OA\RequestBody(description: 'User data', required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserDto'))]
    #[OA\Tag(name: 'Setup/User')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(#[MapRequestPayload] UserDto $data): JsonResponse
    {
        try {
            $username = $data->username;
            $b = $this->userService->existsByUsername($username);
            if ($b) {
                throw new BadRequestException('A user with that username already exists');
            }

            $role = $this->roleService->findById($data->role_id);
            if ($role === null) {
                throw new NotFoundHttpException('Role not found');
            }

            $o = new User();
            $o->username = $data->username;
            $o->setPassword($data->password);
            $o->first_name = $data->first_name;
            $o->last_name = $data->last_name;
            $o->roles = [$role];
            $this->userService->save($o);
            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/user/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/User')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(int $id): JsonResponse
    {
        try {
            $user = $this->userService->findById($id);
            if ($user === null) {
                throw new NotFoundHttpException('User not found');
            }

            return $this->json($user);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/user/{id}', methods: ['PUT'])]
    #[OA\RequestBody(description: 'User data', required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserDto'))]
    #[OA\Tag(name: 'Setup/User')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(int $id, #[MapRequestPayload] UserDto $data): JsonResponse
    {
        try {
            $username = $data->username;
            $password = $data->password;
            $b = $this->userService->existsByOtherUsername($username, $id);
            if ($b) {
                throw new BadRequestException('A user with that username already exists');
            }

            $role = $this->roleService->findById($data->role_id);
            if ($role === null) {
                throw new NotFoundHttpException('Role not found');
            }

            $o = new User();
            $o->id = $id;
            $o->username = $data->username;
            $o->setPassword('');
            $o->first_name = $data->first_name;
            $o->last_name = $data->last_name;
            $o->roles = [$role];

            if ($password != '********') {
                $o->setPassword($password);
            }

            $this->userService->update($o);
            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/user/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'Setup/User')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->userService->deleteById($id);
            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
}