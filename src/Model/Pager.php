<?php

namespace App\Model;

class Pager
{
    public int $total;
    public int $pageNum;
    public int $pageSize;
    
    public function __construct(int $total = 0, int $pageNum = 1, int $pageSize = 10)
    {
        $this->total = $total;
        $this->pageNum = $pageNum;
        $this->setPageSize($pageSize);
    }
    
    private function setPageSize(int $pageSize): void
    {
        if (($this->total < $pageSize || $pageSize < 1) && $this->total > 0) {
            $this->pageSize = $this->total;
        } else {
            $this->pageSize = $pageSize;
        }
        
        if ($this->getTotalPages() < $this->pageNum) {
            $this->pageNum = $this->getTotalPages();
        }
        
        if ($this->pageNum < 1) {
            $this->pageNum = 1;
        }
    }
    
    public function getLowerBound(): int
    {
        return ($this->pageNum - 1) * $this->pageSize;
    }
    
    public function getUpperBound(): int
    {
        $x = $this->pageNum * $this->pageSize;
        if ($this->total < $x) {
            $x = $this->total;
        }
        
        return $x;
    }
    
    public function getTotalPages(): int
    {
        if ($this->pageSize <= 0) {
            return 0;
        }
        
        $v = (float)$this->total / (float)$this->pageSize;
        $x = ceil($v);
        return (int)$x;
    }
}