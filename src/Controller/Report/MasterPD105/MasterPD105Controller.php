<?php

namespace App\Controller\Report\MasterPD105;

use Psr\Log\LoggerInterface;

use App\Model\Pager;
use App\Service\MongoDbService;
use App\Service\ReportService;
use App\Service\HelperService;
use App\Controller\Report\MasterPD105\COLUMN_MAP;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\BSON\ObjectId;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

use OpenApi\Attributes as OA;

#[Route('/api/master-pd105')]
class MasterPD105Controller extends AbstractController
{
    private MongoDbService $mongoDbService;
    private ReportService $reportService;
    private HelperService $helperService;
    private LoggerInterface $logger;
    
    public function __construct(MongoDbService $mongoDbService, ReportService $reportService, HelperService $helperService, LoggerInterface $logger)
    {
        $this->mongoDbService = $mongoDbService;
        $this->reportService = $reportService;
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

    private function getCollection(Client $cli, string $username, string $vt): Collection
    {
        $db = $this->getDb($cli, $vt);
        $s = "__{$username}__";
        $col = $db->getCollection($s);
        return $col;
    }

    private function getDb(Client $cli, string $vt): Database
    {
        $prefix = $_ENV['MONGODB_PREFIX'];
        $suffix = $prefix === 'prod' ? '_prod' : '';
        $s = "master_pd105{$suffix}";
        $db = $cli->getDatabase($s);
        return $db;
    }

    #[Route('/export/rpt1', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD105')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function jsonPD105(#[MapQueryParameter] string $datefrom = '', #[MapQueryParameter] string $dateto = ''): StreamedJsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new NotFoundHttpException('User not found', code: 404);
            }

            $username = $user->getUserIdentifier();
            $cli = $this->mongoDbService->getClient();
            $col = $this->getCollection($cli, $username, '0');
            $ls = $col->find([])->toArray();
            $ls = $this->helperService->processDoc($ls);

            $dt1 = explode('-', $datefrom);
            $dt2 = explode('-', $dateto);
            $ds1 = "{$dt1[2]}{$dt1[1]}{$dt1[0]}";
            $ds2 = "{$dt2[2]}{$dt2[1]}{$dt2[0]}";

            $forms = [];
            foreach ($ls as $d) {
                $person = [
                    'refPersonTitleCode' => $this->reportService->refPersonTitleCode($d),
                    'fullName' => (string)$d['PATIENT_NAME'],
                    'refIdentificationTypeCode' => $this->reportService->refIdentificationTypeCode($d),
                    'identificationNo' => (string)$d['DOCUMENT_NUMBER'],
                    'refAddressTypeCode' => 'C',
                    'street1' => (string)$d['STREET1'],
                    'street2' => (string)$d['STREET2'],
                    'refCityCode' => $this->reportService->refCityCode($d),
                    'refPostCode' => (string)$d['POSTCODE'],
                    'refStateCode' => $this->reportService->refStateCode($d),
                    'refCountryCode' => $this->reportService->refCitizenshipCode($d),
                    'refContactTypeCode' => '02',
                    'contactInfo' => (string)$d['HOME_PHONE'],
                ];

                $nok = [
                    'refPersonTitleCode' => $this->reportService->refPersonTitleCodeNOK($d),
                    'fullName' => (string)$d['PATIENT_NOK_NAME'],
                    'refIdentificationTypeCode' => $this->reportService->refIdentificationTypeCodeNOK($d),
                    'identificationNo' => (string)$d['NOK_ID'],
                    'refAddressTypeCode' => 'C',
                    'street1' => (string)$d['NOK_STREET1'],
                    'street2' => (string)$d['NOK_STREET2'],
                    'refCityCode' => $this->reportService->refCityCodeNOK($d),
                    'refPostCode' => (string)$d['NOK_POSTCODE'],
                    'refStateCode' => $this->reportService->refStateCodeNOK($d),
                    'refCountryCode' => $this->reportService->refCitizenshipCodeNOK($d),
                    'refContactTypeCode' => '02',
                    'contactInfo' => (string)$d['NOK_MOBILE_PHONE'],
                ];

                $m = [
                    'rn' => (string)$d['ACCOUNT_NO'],
                    'mrn' => (string)$d['PRN'],
                    'eventDate' => sprintf('%s %s:00', $d['REGISTRATION_DATE'], $d['REGISTRATION_TIME']),
                    'isPoliceCase' => '02',
                    'internalReferral' => 'false',
                    'refReferralSourceCode' => $this->reportService->refReferralSourceCode($d),
                    'refGenderCode' => $this->reportService->refGenderCode($d),
                    'dob' => (string)$d['DOB'],
                    'refMaritalStatusCode' => $this->reportService->refMaritalStatusCode($d),
                    'refReligionCode' => $this->reportService->refReligionCode($d),
                    'refCitizenshipCode' => $this->reportService->refCitizenshipCode($d),
                    'refEthnicCode' => $this->reportService->refEthnicCode($d),
                    'height' => $this->helperService->getNum((string)$d['HEIGHT']),
                    'weight' => $this->helperService->getNum((string)$d['WEIGHT']),
                    'refForeignerOriginCountryCode' => $this->reportService->refForeignerOriginCountryCode($d),
                    'refForeignerResidenceCountryCode' => $this->reportService->refForeignerResidenceCountryCode($d),
                    'refPersonCategoryCode' => $this->reportService->refPersonCategoryCode($d),
                    'refRelationshipCode' => $this->reportService->refRelationshipCode($d),
                    'refWardTransitionTypeCode' => 'A',
                    'wardDateTime' => sprintf('%s %s:00', $d['ADMISSION_DATE'], $d['ADMISSION_TIME']),
                    'wardCode' => (string)$d['WARD_NO'],
                    'refDisciplineCode' => $this->reportService->refDisciplineCode1($d),
                    'refSpecialityCode' => $this->reportService->refDisciplineCode1($d),
                    'refSubSpecialityCode' => $this->reportService->refDisciplineCode1($d),
                    'refWardClassCode' => $this->reportService->refWardClassCode($d),
                    'refWardCategoryCode' => '00',
                    'dateOfDeath' => "{$d['DEATH_DATE']} 00:00:00",
                    'mortuaryRegTime' => "{$d['DEATH_DATE']} 00:00:00",
                    'isBabyAlive' => (string)$d['RESULT_OF_BIRTH'],
                    'causesOfDeath' => '00',
                    'certificateNo' => '00',
                    'medicoLegal' => '00',
                    'autopsy' => '00',
                    'person' => $person,
                    'nextOfKins' => $nok,
                ];

                $forms[] = $m;
            }

            $facilityCode = $_ENV['FACILITY_CODE'];
            $filename = "{$facilityCode}_{$ds1}_{$ds2}_PD105.json";

            $res = new StreamedJsonResponse(
                [
                    'filename' => $filename,
                    'admissionFrom' => $datefrom,
                    'admissionTo' => $dateto,
                    'refServiceTypeCode' => '01',
                    'facilityCode' => $facilityCode,
                    'forms' => $forms,
                ], 200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => "attachment; filename=$filename",
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'filename' => $filename,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/export/rpt1/xlsx', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD105')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function xlsx(
        #[MapQueryParameter] string $vt = '0',
        #[MapQueryParameter] string $datefrom = '',
        #[MapQueryParameter] string $dateto = ''
    ): StreamedResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $username = $user->getUserIdentifier();
            $cli = $this->mongoDbService->getClient();
            $col = $this->getCollection($cli, $username, $vt);
            $ls = $col->find([])->toArray();
            $ls = $this->helperService->processDoc($ls);

            $dt1 = explode('-', $datefrom);
            $dt2 = explode('-', $dateto);
            $ds1 = "{$dt1[2]}{$dt1[1]}{$dt1[0]}";
            $ds2 = "{$dt2[2]}{$dt2[1]}{$dt2[0]}";

            $x = 'PD105';
            $facilityCode = $_ENV['FACILITY_CODE'];
            $filename = "{$facilityCode}_{$ds1}_{$ds2}_{$x}.xlsx";

            return new StreamedResponse(function () use ($ls, $filename) {
                $this->helperService->getXlsx($filename, COLUMN_MAP, $ls);
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=$filename",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            print_r($e->getTrace());
            $this->handleError($e);
        }
    }

    #[Route('/rpt1', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD105')]
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
    ): StreamedJsonResponse
    {
        try {
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
            $ls = $this->helperService->processDoc($ls);
            return new StreamedJsonResponse(
                [
                    'columnmaps' => COLUMN_MAP,
                    'total_count' => $total,
                    'total_page' => $pg->getTotalPages(),
                    'page' => $pg->pageNum,
                    'data' => $ls,
                    'datefrom' => $dateFrom,
                    'dateto' => $dateTo,
                ], 200,
                [
                    'Content-Type' => 'application/json',
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/rpt1/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD105')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(string $id, #[MapQueryParameter] string $vt = '0'): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $username = $user->getUserIdentifier();
            $cli = $this->mongoDbService->getClient();
            $col = $this->getCollection($cli, $username, $vt);
            $ls = $col->find(['_id' => new ObjectId($id)])->toArray();
            $ls = $this->helperService->processDoc($ls);

            if (!empty($ls)) {
                $o = $ls[0];
                $res = $this->json($o);
                $res->setEncodingOptions($res->getEncodingOptions() | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                return $res;
            }

            throw new NotFoundHttpException('Record not found', code: 404);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/rpt1/{id}', methods: ['PUT'])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object'))]
    #[OA\Tag(name: 'Report/MasterPD105')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(Request $request, string $id, #[MapQueryParameter] string $vt = '0'): JsonResponse
    {
        try {
            $user = $this->getUser();
            if ($user === null) {
                throw new UnauthorizedHttpException('User not found', code: 401);
            }

            $username = $user->getUserIdentifier();
            $cli = $this->mongoDbService->getClient();
            $col = $this->getCollection($cli, $username, $vt);
            $data = json_decode($request->getContent(), false);
            $col->findOneAndUpdate(['_id' => new ObjectId($id)], ['$set' => $data]);
            return $this->json(['success' => 1]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }
}