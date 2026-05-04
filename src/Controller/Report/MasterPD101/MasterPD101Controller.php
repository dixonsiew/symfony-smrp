<?php

namespace App\Controller\Report\MasterPD101;

use Psr\Log\LoggerInterface;

use App\Model\Pager;
use App\Service\MongoDbService;
use App\Controller\Report\MasterPD101\COLUMN_MAP;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

use OpenApi\Attributes as OA;

class MasterPD101Controller extends AbstractController
{
    private MongoDbService $mongoDbService;
    
    public function __construct(MongoDbService $mongoDbService)
    {
        $this->mongoDbService = $mongoDbService;
    }
    
    #[Route('/api/master-pd101/rpt1', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD101')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(
        #[MapQueryParameter] int $_page = 1, #[MapQueryParameter] int $_limit = 20, 
        #[MapQueryParameter] string $vt = '0',
        #[MapQueryParameter] string $datefrom = '',
        #[MapQueryParameter] string $dateto = ''
    ): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new NotFoundHttpException('User not found', code: 404);
        }

        $username = $user->getUserIdentifier();
        $cli = $this->mongoDbService->getClient();
        $db = $this->getDb($cli, $vt);
        $col = $db->getCollection("__{$username}__");
        $col2 = $db->getCollection("__{$username}-q__");
        $total = $col->countDocuments([]);
        $t2 = $col2->countDocuments([]);

        $dateFrom = $datefrom;
        $dateTo = $dateto;

        if ($t2 > 0) {
            $res = $col2->find([]);
            $ld = $res->toArray();
            $dateFrom = $ld[0]['datefrom'];
            $dateTo = $ld[0]['dateto'];
        }

        $pg = new Pager($total, $_page, $_limit);
        $filter = [];
        $options = [
            'skip' => $pg->getLowerBound(),
            'limit' => $pg->pageSize,
        ];
        $ls = $col->find($filter, $options)->toArray();
        return $this->json([
            'columnmaps' => COLUMN_MAP,
            'total_count' => $total,
            'total_page' => $pg->getTotalPages(),
            'page' => $pg->pageNum,
            'data' => $ls,
            'datefrom' => $dateFrom,
            'dateto' => $dateTo,
        ]);
    }

    private function getCollection(Client $cli, string $username, string $vt): Collection
    {
        $db = $this->getDb($cli, $vt);
        $s = "__{$username}__";
        $col = $db->getCollection($s);
        return $col;
    }

    private function getDb(Client $cli, string $vt): Database
    {
        $suffix = '';
        $db = null;
        $prefix = $_ENV['MONGODB_PREFIX'];
        if ($prefix === 'prod') {
            $suffix = '_prod';
        }

        if ($vt === '0') {
            $s = "master_pd101$suffix";
            $db = $cli->getDatabase($s);
        } else {
            $s = "master_rh101$suffix";
            $db = $cli->getDatabase($s);
        }

        return $db;
    }
}