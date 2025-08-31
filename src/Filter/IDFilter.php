<?php

namespace Kumakyoo\OmaLib\Filter;

class IDFilter extends Filter
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function keep($e) : bool
    {
        return $e->id==$this->id;
    }

    public function countable() : bool
    {
        return false;
    }
}
