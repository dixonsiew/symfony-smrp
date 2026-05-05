<?php

namespace App\Service;

use function strlen;

use DateTime;
use MongoDB\Model\BSONDocument;

class HelperService
{
    public function getSort(string $sort, string $_sortby, string $_sortdir): array
    {
        $sortBy = $_sortby;
        $sortDir = $_sortdir;
        if (strlen($sort) > 0) {
            $lis = explode('$', $sort);
            $s = $lis[0];
            $arr = explode(':', $s);
            $sortBy = $arr[0];
            $sortDir = $arr[1];
        }
        return [$sortBy, $sortDir];
    }

    public function getDateStr($v): string
    {
        $date = DateTime::createFromFormat('j/n/Y h:i:s A', $v);
        if ($date === false) {
            return $v;
        }

        return $date->format('Y-m-d');
    }

    public function setValue(BSONDocument &$x, string $ofield, string $srcField)
    {
        $v = $x[$srcField];
        if (isset($x[$ofield])) {
            $s = $x[$ofield];
            if ($s === 'N/A') {
                $x[$ofield] = $v;
            }
        } else {
            $x[$ofield] = $v;
        }

        if ($x[$ofield] === 'undefined') {
            $x[$ofield] = 'N/A';
        }
    }

    public function processDoc(array $lx): array
    {
        $ls = [];
        $na = "N/A";
        foreach ($lx as $x) {
            $idString = (string) $x['_id'];
            $x['_id'] = $idString;
            if ($x->offsetExists('ADMISSION_DATE')) {
                $x['ADMISSION_DATE'] = $this->getDateStr($x['ADMISSION_DATE']);
            }

            if ($x->offsetExists('DISCHARGE_DATE')) {
                $x['DISCHARGE_DATE'] = $this->getDateStr($x['DISCHARGE_DATE']);
            }

            if ($x->offsetExists('DEATH_DATE')) {
                $x['DEATH_DATE'] = $this->getDateStr($x['DEATH_DATE']);
            }

            if ($x->offsetExists('DELIVERY_DATE')) {
                $x['DELIVERY_DATE'] = $this->getDateStr($x['DELIVERY_DATE']);
            }

            if ($x->offsetExists('PATIENT_NOK_NAME')) {
                $s = $x['PATIENT_NOK_NAME'];
                if ($na === $s) {
                    $x['NOK_STREET1'] = $na;
                    $x['NOK_STREET2'] = $na;
                    $x['NOK_CITYCODE'] = $na;
                    $x['NOK_POSTCODE'] = $na;
                    $x['NOK_OCITY'] = $na;
                    $x['NOK_NATIONALITY'] = $na;
                } else {
                    $this->setValue($x, 'NOK_STREET1', 'STREET1');
                    $this->setValue($x, 'NOK_STREET2', 'STREET2');
                    $this->setValue($x, 'NOK_CITYCODE', 'CITYCODE');
                    $this->setValue($x, 'NOK_POSTCODE', 'POSTCODE');
                    $this->setValue($x, 'NOK_OCITY', 'OCITY');
                    $this->setValue($x, 'NOK_NATIONALITY', 'NATIONALITY');
                }
            } else {
                $x['NOK_STREET1'] = $na;
                $x['NOK_STREET2'] = $na;
                $x['NOK_CITYCODE'] = $na;
                $x['NOK_POSTCODE'] = $na;
                $x['NOK_OCITY'] = $na;
                $x['NOK_NATIONALITY'] = $na;
            }
            
            $ls[] = $x;
        }

        return $ls;
    }
}