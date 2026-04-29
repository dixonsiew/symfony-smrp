<?php

namespace App\Controller\Report\MasterPD101;

const COLUMN_MAP = [
    new ColumnMap("ACCOUNT_NO", "ACCOUNT NO"),
    new ColumnMap("PRN", "PRN"),
    new ColumnMap("REGISTRATION_DATE", "REG DATE"),
    new ColumnMap("REGISTRATION_TIME", "REG TIME"),
    new ColumnMap("TITLE", "TITLE"),

    new ColumnMap("PATIENT_NAME", "NAME"),
    new ColumnMap("GENDER", "GENDER"),
    new ColumnMap("DOB", "DOB"),
    new ColumnMap("MARITAL_STATUS", "MARITAL STATUS"),
    new ColumnMap("RELIGION", "RELIGION"),

    new ColumnMap("NATIONALITY", "NATIONALITY"),
    new ColumnMap("ETHNIC_GROUP", "ETHNIC GROUP"),
    new ColumnMap("OCCUPATION", "OCCUPATION"),
    new ColumnMap("HEIGHT", "HEIGHT"),
    new ColumnMap("WEIGHT", "WEIGHT"),

    new ColumnMap("COUNTRY_OF_BIRTH", "COUNTRY OF BIRTH"),
    new ColumnMap("REFPERSONCATEGORYCODE", "PATIENT CATEGORY"),
    new ColumnMap("DOCUMENT_TYPE", "DOC TYPE"),
    new ColumnMap("DOCUMENT_NUMBER", "DOC NO"),
    new ColumnMap("STREET1", "STREET1"),

    new ColumnMap("STREET2", "STREET2"),
    new ColumnMap("CITYCODE", "STREET3"),
    new ColumnMap("POSTCODE", "POSTCODE"),
    new ColumnMap("OCITY", "STATE"),
    new ColumnMap("COUNTRY", "COUNTRY"),

    new ColumnMap("HOME_PHONE", "HOME PHONE"),
    new ColumnMap("NOK_TITLE", "NOK TITLE"),
    new ColumnMap("PATIENT_NOK_NAME", "NOK NAME"),
    new ColumnMap("RELATION_DESCRIPTION", "RELATIONSHIP"),
    new ColumnMap("NOK_ID_TYPE", "NOK DOC TYPE"),

    new ColumnMap("NOK_ID", "NOK DOC NO"),
    new ColumnMap("NOK_STREET1", "STREET1"),
    new ColumnMap("NOK_STREET2", "STREET2"),
    new ColumnMap("NOK_CITYCODE", "STREET3"),
    new ColumnMap("NOK_POSTCODE", "POSTCODE"),

    new ColumnMap("NOK_OCITY", "STATE"),
    new ColumnMap("COUNTRY", "COUNTRY"),
    new ColumnMap("NOK_MOBILE_PHONE", "NOK MOBILE NO"),
    new ColumnMap("ADMISSION_DATE", "ADMISSION DATE"),
    new ColumnMap("ADMISSION_TIME", "ADMISSION TIME"),

    new ColumnMap("WARD_NO", "WARD NO"),
    new ColumnMap("PRIMARY_SPECIALITY", "PRIMARY SPECIALITY"),
    new ColumnMap("PAYMENT_CLASS_CODE", "PAYMENT CLASS"),
];