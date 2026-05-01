<?php

namespace App\Controller\Report;

class ColumnMap
{
    public string $field;
    public string $text;
    
    public function __construct(string $field, string $text)
    {
        $this->field = $field;
        $this->text = $text;
    }
}