<?php

namespace App\Controller\Setup;

use Psr\Log\LoggerInterface;

use App\Constants\Constants;
use App\Dto\CommonSetupDto;
use App\Dto\KeywordDto;
use App\Entity\CommonSetup;
use App\Model\Pager;
use App\Service\CommonSetupService;
use App\Service\HelperService;
use App\Service\UserService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

class PersonCategoryCodeController extends AbstractController
{
    private CommonSetupService $commonSetupService;
    private UserService $userService;
    private HelperService $helperService;
    private LoggerInterface $logger;

    private const table = 'person_category_code';

    public function __construct(CommonSetupService $commonSetupService, UserService $userService,
        HelperService $helperService, LoggerInterface $logger)
    {
        $this->commonSetupService = $commonSetupService;
        $this->userService = $userService;
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

    #[Route('/api/lookup/person-category-codes', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CommonSetup::class, groups: ['user:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function lookupList(): JsonResponse
    {
        try {
            $lx = $this->commonSetupService->findAll(self::table, 0, 0, '', '');
            return $this->json($lx);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-codes', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CommonSetup::class, groups: ['user:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(#[MapQueryParameter] int $_page = 1, #[MapQueryParameter] int $_limit = 20, #[MapQueryParameter] string $sort = ''): JsonResponse
    {
        $sortBy = 'code';
        $sortDir = 'asc';
        if (strlen($sort) > 0) {
            [$sortBy, $sortDir] = $this->helperService->getSort($sort, $sortBy, $sortDir);
        }

        try {
            $total = $this->commonSetupService->count(self::table);
            $pg = new Pager($total, $_page, $_limit);
            $lx = $this->commonSetupService->findAll(self::table, $pg->getLowerBound(), $pg->pageSize, $sortBy, $sortDir);
            $res = new JsonResponse($lx);
            $res->headers->set(Constants::X_TOTAL_COUNT, "$total");
            $res->headers->set(Constants::X_TOTAL_PAGE, "{$pg->getTotalPages()}");
            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-codes', methods: ['POST'])]
    #[OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/KeywordDto'))]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CommonSetup::class, groups: ['user:read']))
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
        $sortBy = 'code';
        $sortDir = 'asc';
        $key = '%' . $data->keyword . '%';

        if (strlen($sort) > 0) {
            [$sortBy, $sortDir] = $this->helperService->getSort($sort, $sortBy, $sortDir);
        }

        try {
            $total = $this->commonSetupService->countByKeyword($key, self::table);
            $pg = new Pager($total, $_page, $_limit);
            $lx = $this->commonSetupService->findByKeyword($key, $pg->getLowerBound(), $pg->pageSize, $sortBy, $sortDir, self::table);
            $res = new JsonResponse($lx);
            $res->headers->set(Constants::X_TOTAL_COUNT, "$total");
            $res->headers->set(Constants::X_TOTAL_PAGE, "{$pg->getTotalPages()}");
            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-code', methods: ['POST'])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CommonSetupDto'))]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(#[MapRequestPayload] CommonSetupDto $data): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $o = new CommonSetup();
            $o->code = $data->code;
            $o->desc = $data->desc;
            $o->ref = $data->ref;
            $o->created_by = $user->getUserId();
            $this->commonSetupService->save($o, self::table);

            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-code/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(int $id): JsonResponse
    {
        try {
            $o = $this->commonSetupService->findById($id, self::table);
            if ($o === null) {
                throw new NotFoundHttpException('Record not found', code: 404);
            }

            return $this->json($o);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-code/{id}', methods: ['PUT'])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CommonSetupDto'))]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(int $id, #[MapRequestPayload] CommonSetupDto $data): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $o = $this->commonSetupService->findById($id, self::table);
            if ($o === null) {
                throw new NotFoundHttpException('Record not found', code: 404);
            }

            $o->code = $data->code;
            $o->desc = $data->desc;
            $o->ref = $data->ref;
            $o->modified_by = $user->getUserId();
            $this->commonSetupService->update($o, self::table);

            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/api/person-category-code/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'Setup/PersonCategoryCode')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $this->commonSetupService->deleteById($id, $user->getUserId(), self::table);

            return $this->json([
                'success' => 1
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
}