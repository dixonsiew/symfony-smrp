<?php

namespace App\Controller\Report\MasterPD102;

use Psr\Log\LoggerInterface;

use App\Model\Pager;
use App\Service\MongoDbService;
use App\Service\ReportService;
use App\Service\HelperService;
use App\Controller\Report\MasterPD102\COLUMN_MAP;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\BSON\ObjectId;

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

#[Route('/api/master-pd102')]
class MasterPD102Controller extends AbstractController
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
        $s = "master_pd102{$suffix}";
        $db = $cli->getDatabase($s);
        return $db;
    }

    #[Route('/export/rpt1', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD102')]
    #[OA\Response(
        response: 200,
        description: 'Successful response'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function jsonPD102(#[MapQueryParameter] string $datefrom = '', #[MapQueryParameter] string $dateto = ''): JsonResponse
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
                    'height' => $this->helperService->getNum($d['PERSON_HEIGHT']),
                    'weight' => $this->helperService->getNum($d['PERSON_WEIGHT']),
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
                    'gravida' => (string)$d['GRAVIDA'],
                    'para' => (string)$d['PARITY'],
                    'periodOfGestationDay' => (float)$d['GESTATION_PERIOD'] * 7,
                    'periodOfGestationWeek' => (string)$d['GESTATION_PERIOD'],
                    'isMotherAlive' => $d['ISMOTHERALIVE'],
                    'refAntenatalCareCode' => (string)$d['REFANTENATALCARECODE'],
                    'refLabourStatusCode' => (string)$d['LABOUR_METHOD'],
                    'labourDateTime' => "{$d['DELIVERY_DATE']} 00:00:00",
                    'bornBeforeArrival' => 'N/A',
                    'isBabyAlive' => (string)$d['RESULT_OF_BIRTH'],
                    'refLabourTypeCode' => 'N/A',
                    'refLabourModeCode' => $this->reportService->refLabourModeCode($d),
                    'refGenderCode1' => $this->reportService->refGenderCode1($d),
                    'birthWeight' => $this->helperService->getNum((string)$d['WEIGHT']),
                    'birthLength' => $this->helperService->getNum((string)$d['LENGTH']),
                    'birthHeadCircumference' => 'N/A',
                    'refBloodTypeCode' => 'N/A',
                    'refRhesusCode' => 'N/A',
                    'refMin1ApgarScoreCode' => 'N/A',
                    'refMin5ApgarScoreCode' => 'N/A',
                    'refLabourComplicationCodes' => 'N/A',
                    'person' => $person,
                    'nextOfKins' => $nok,
                ];

                $forms[] = $m;
            }

            $facilityCode = $_ENV['FACILITY_CODE'];
            $filename = "{$facilityCode}_{$ds1}_{$ds2}_PD102.json";

            $res = $this->json([
                'filename' => $filename,
                'admissionFrom' => $datefrom,
                'admissionTo' => $dateto,
                'refServiceTypeCode' => '01',
                'facilityCode' => $facilityCode,
                'forms' => $forms,
            ]);

            $res->headers->set('Content-Type', 'application/json');
            $res->headers->set('Content-Disposition', "attachment; filename=$filename");
            $res->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $res->headers->set('Pragma', 'no-cache');
            $res->headers->set('Expires', '0');
            $res->headers->set('filename', $filename);

            $res->setEncodingOptions($res->getEncodingOptions() | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return $res;
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/rpt1', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD102')]
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
            return $this->json([
                'columnmaps' => COLUMN_MAP,
                'total_count' => $total,
                'total_page' => $pg->getTotalPages(),
                'page' => $pg->pageNum,
                'data' => $ls,
                'datefrom' => $dateFrom,
                'dateto' => $dateTo,
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/rpt1/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'Report/MasterPD102')]
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
                return $this->json($o);
            }

            throw new NotFoundHttpException('Record not found', code: 404);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    #[Route('/rpt1/{id}', methods: ['PUT'])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object'))]
    #[OA\Tag(name: 'Report/MasterPD102')]
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