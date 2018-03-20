<?php

class ArrayProvider
{
    public $data = [];

    public function __construct($rows, $cols)
    {
        for ($i = 0; $i < $rows; $i++) {
            $this->data[] = array_fill(0, $cols, null);
        }
    }

    public function getValue($row, $col)
    {
        return $this->data[--$row][--$col];
    }

    public function setValue($row, $col, $val)
    {
        $this->data[--$row][--$col] = $val;
    }

    public function getSize()
    {
        return count($this->data);
    }
}

class SudokuMap extends ArrayProvider
{
    private function getLine($row = null, $col = null)
    {
        $result = [];
        for ($i = 1; $i <= $this->getSize(); $i++) {
            if ($row) {
                $result[] = $this->getValue($row, $i);
            } else {
                if ($col) {
                    $result[] = $this->getValue($i, $col);
                }
            }
        }

        return $result;
    }

    public function getRow($row)
    {
        return $this->getLine($row);
    }

    public function getCol($col)
    {
        return $this->getLine(null, $col);
    }

    public function getSquareList($row_start, $col_start, $row_end, $col_end)
    {
        $result = [];
        for ($row = $row_start; $row <= $row_end; $row++) {
            for ($col = $col_start; $col <= $col_end; $col++) {
                $result[] = $this->getValue($row, $col);
            }
        }

        return $result;
    }

    public function fill($map)
    {
        if (count($map) != $this->getSize()) {
            return false;
        }

        foreach ($map as $row => $cols) {
            foreach ($cols as $col => $val) {
                $this->setValue($row + 1, $col + 1, $val);
            }
        }
    }
}

class SudokuCheckerError extends Exception
{
    public function __construct()
    {
        $this->message = 'Something wrong with Sudoku';
    }
}

class SudokuCheckerLineError extends SudokuCheckerError
{
    public function __construct($row = null, $col = null)
    {
        parent::__construct();
        if ($row) {
            $this->message .= " in row $row";
        } else {
            if ($col) {
                $this->message .= " in col $col";
            }
        }
    }
}

class SudokuCheckerSquareError extends SudokuCheckerError
{
    public function __construct($row_start, $col_start, $row_end, $col_end)
    {
        parent::__construct();
        $this->message .= " in square between ($row_start, $col_start) and ($row_end, $col_end)";
    }
}

class SudokuChecker
{
    /** @var SudokuMap $map */
    private $map;

    public function __construct(SudokuMap $map)
    {
        $this->setMap($map);
    }

    public function getMap()
    {
        return $this->map;
    }

    public function setMap(SudokuMap $map)
    {
        $this->map = $map;
    }

    public function check()
    {
        $map = $this->getMap();
        $size = $map->getSize();
        for ($i = 1; $i <= $size; $i++) {
            foreach ([$map->getRow($i), $map->getCol($i)] as $list_index => $list) {
                if (!$this->isValidList($list)) {
                    if ($list_index == 0) {
                        throw new SudokuCheckerLineError($i);
                    } else {
                        if ($list_index == 1) {
                            throw new SudokuCheckerLineError(null, $i);
                        }
                    }
                }
            }
        }

        for ($row_start = 1; $row_start <= ($size - 3) + 1; $row_start += 3) {
            for ($col_start = 1; $col_start <= ($size - 3) + 1; $col_start += 3) {
                $row_end = $row_start + 2;
                $col_end = $col_start + 2;
                $list = $map->getSquareList($row_start, $col_start, $row_end, $col_end);
                if (!$this->isValidList($list)) {
                    throw new SudokuCheckerSquareError($row_start, $col_start, $row_end, $col_end);
                }
            }
        }

        return true;
    }

    private function isValidList($list)
    {
        return count(array_unique(array_filter($list))) == count($list);
    }
}

$map = new SudokuMap(9, 9);
$map->fill([
    [1, 8, 2, 5, 4, 3, 6, 9, 7],
    [9, 6, 5, 1, 7, 8, 3, 4, 2],
    [7, 4, 3, 9, 6, 2, 8, 1, 5],
    [3, 7, 4, 8, 9, 6, 5, 2, 1],
    [6, 2, 8, 4, 5, 1, 7, 3, 9],
    [5, 1, 9, 2, 3, 7, 4, 6, 8],
    [2, 9, 7, 6, 8, 4, 1, 5, 3],
    [4, 3, 1, 7, 2, 5, 9, 8, 6],
    [8, 5, 6, 3, 1, 9, 2, 7, 4]
]);
$checker = new SudokuChecker($map);
try {
    if ($checker->check()) {
        echo 'Everything is right!';
    }
} catch (SudokuCheckerError $e) {
    echo $e->getMessage();
}

?>