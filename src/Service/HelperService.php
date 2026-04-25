<?php

namespace App\Service;

use function strlen;

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
}