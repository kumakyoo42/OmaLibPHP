<?php

namespace Kumakyoo\OmaLib\Filter;

class BlockSliceFilter extends Filter
{
    private $key;
    private $value;

    public function __construct($key,$value)
    {
        $this->key = $key===null?'':$key;
        $this->value = $value===null?'':$value;
    }

    public function needsBlock($key) : bool
    {
        return $key===$this->key;
    }

    public function needsSlice($value) : bool
    {
        return $value===$this->value;
    }
}
