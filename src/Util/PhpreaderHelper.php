<?php

namespace App\Util;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class PhpreaderHelper implements IReadFilter
{
    private $minRow=0;
    private $maxRow=55555;
    private $minCol='A';
    private $maxCol='Z';

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        // Read rows 1 to 7 and columns A to E only
        if (($row >= $this->minRow) && ($row <= $this->maxRow)) {
            if (in_array($columnAddress, range($this->minCol, $this->maxCol))) {
                // dump($row, $columnAddress);
                return true;
            }
        }

        return false;
    }

    public function setMinRow($row): self
    {
        $this->minRow = $row;

        return $this;
    }

    public function setMaxRow($row): self
    {
        $this->maxRow = $row;

        return $this;
    }

    public function setMaxCol($col): self
    {
        $this->maxCol = $col;

        return $this;
    }

    public function setMinCol($col): self
    {
        $this->minCol = $col;

        return $this;
    }









}
