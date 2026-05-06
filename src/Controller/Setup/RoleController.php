<?php

namespace App\Controller\Setup;

use Psr\Log\LoggerInterface;

use App\Constants\Constants;
use App\Entity\Role;
use App\Service\HelperService;
use App\Service\RoleService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

use OpenApi\Attributes as OA;

use function strlen;

#[Route('/api')]
class RoleController extends AbstractController
{
    private RoleService $roleService;
    private HelperService $helperService;
    private LoggerInterface $logger;

    public function __construct(RoleService $roleService, HelperService $helperService, LoggerInterface $logger)
    {
        $this->roleService = $roleService;
        $this->helperService = $helperService;
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

    #[Route('/lookup/groups', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/Role')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Role::class, groups: ['user:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function lookupList(): JsonResponse
    {
        try {
            $lx = $this->roleService->findAll('name', 'asc');
            return $this->json($lx);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
}