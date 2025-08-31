<?php

namespace Kumakyoo\OmaLib\Filter;

class BlockFilter extends Filter
{
    private $key;

    public function __construct($key)
    {
        $this->key = $key===null?'':$key;
    }

    public function needsBlock($key) : bool
    {
        return $key===$this->key;
    }
}
