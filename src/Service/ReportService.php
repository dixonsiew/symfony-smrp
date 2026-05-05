<?php

namespace App\Service;

use MongoDB\Model\BSONDocument;

use App\Constants\Constants;
use App\Service\CommonSetupService;

class ReportService
{
    private CommonSetupService $commonSetupService;

    public function __construct(CommonSetupService $commonSetupService)
    {
        $this->commonSetupService = $commonSetupService;
    }

    public function refReferralSourceCode(BSONDocument $doc): string
    {
        return $this->getCode("REFERRAL", $doc, "referral");
    }

    public function refPersonTitleCode(BSONDocument $doc): string
    {
        return $this->getCode("TITLE", $doc, "title");
    }

    public function refGenderCode(BSONDocument $doc): string
    {
        return $this->getCode("GENDER", $doc, "gender");
    }

    public function refGenderCode1(BSONDocument $doc): string
    {
        return $this->getCode("CHILD_SEX", $doc, "gender");
    }

    public function refMaritalStatusCode(BSONDocument $doc): string
    {
        return $this->getCode("MARITAL_STATUS", $doc, "marital_status");
    }

    public function refReligionCode(BSONDocument $doc): string
    {
        return $this->getCode("RELIGION", $doc, "religion");
    }

    public function refCitizenshipCode(BSONDocument $doc): string {
        return $this->getCode("NATIONALITY", $doc, "country");
    }

    public function refCitizenshipCodeNOK(BSONDocument $doc): string {
        return $this->getCode("NOK_NATIONALITY", $doc, "country");
    }

    public function refEthnicCode(BSONDocument $doc): string
    {
        return $this->getCode("ETHNIC_GROUP", $doc, "ethnic_group");
    }

    public function refForeignerOriginCountryCode(BSONDocument $doc): string
    {
        return $this->getCode("COUNTRY_OF_BIRTH", $doc, "country");
    }

    public function refForeignerResidenceCountryCode(BSONDocument $doc): string
    {
        return $this->getCode("REFFOREIGNRCOUNTRYCODE", $doc, "country");
    }

    public function refPersonCategoryCode(BSONDocument $doc): string
    {
        return $this->getCode("REFPERSONCATEGORYCODE", $doc, "person_category_code");
    }

    public function refIdentificationTypeCode(BSONDocument $doc): string
    {
        return $this->getCode("DOCUMENT_TYPE", $doc, "id_type");
    }

    public function refCityCode(BSONDocument $doc): string
    {
        return $this->getCode("CITYCODE", $doc, "city");
    }

    public function refCityCodeNOK(BSONDocument $doc): string
    {
        return $this->getCode("NOK_CITYCODE", $doc, "city");
    }

    public function refStateCode(BSONDocument $doc): string
    {
        return $this->getCode("OCITY", $doc, "state");
    }

    public function refStateCodeNOK(BSONDocument $doc): string
    {
        return $this->getCode("NOK_OCITY", $doc, "state");
    }

    public function refPersonTitleCodeNOK(BSONDocument $doc): string
    {
        return $this->getCode("NOK_TITLE", $doc, "title");
    }

    public function refRelationshipCode(BSONDocument $doc): string
    {
        return $this->getCode("RELATION_DESCRIPTION", $doc, "relationship");
    }

    public function refIdentificationTypeCodeNOK(BSONDocument $doc): string
    {
        return $this->getCode("NOK_ID_TYPE", $doc, "id_type");
    }

    public function refDisciplineCode(BSONDocument $doc): string
    {
        return $this->getCode("PRIMARY_SPECIALITY", $doc, "speciality");
    }

    public function refDisciplineCode1(BSONDocument $doc): string
    {
        return $this->getCode("PRIMARY_SPECIALTY", $doc, "speciality");
    }

    public function refWardClassCode(BSONDocument $doc): string
    {
        return $this->getCode("PAYMENT_CLASS_CODE", $doc, "ward_class");
    }

    public function refDischargeTypeCode(BSONDocument $doc): string
    {
        return $this->getCode('DISCHARGE_REASON', $doc, 'discharge_type');
    }

    public function refDiagnosisItemTypeCode(BSONDocument $doc): string
    {
        return $this->getCode('DIAGNOSIS_DESC', $doc, 'diag_item_type');
    }

    public function refLabourModeCode(DocuBSONDocumentment $doc): string
    {
        return $this->getCode('DELIVERY_TYPE', $doc, 'delivery_type');
    }

    private function getCode(string $key, BSONDocument $doc, string $table): string
    {
        $x = Constants::NO_INFO;
        $s = $this->get($key, $doc);
        if ($s !== '') {
            $o = $this->commonSetupService->findByDesc($s, $table);
            if ($o !== null) {
                $x = $o->code;
            }
        }

        return $x;
    }

    private function get(string $key, BSONDocument $doc): string
    {
        $s = '';
        if (isset($doc[$key])) {
            $s = $doc[$key];
            $s = trim($s);
        }

        return $s;
    }
}