<?php

namespace Kumakyoo\OmaLib\Filter;

class KeyFilter extends Filter
{
    private $key;

    public function __construct($key)
    {
        $this->key = $key===null?'':$key;
    }

    public function keep($e) : bool
    {
        return isset($e->tags[$this->key]);
    }

    public function countable() : bool
    {
        return false;
    }
}
