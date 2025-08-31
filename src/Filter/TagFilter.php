<?php

namespace Kumakyoo\OmaLib\Filter;

class TagFilter extends Filter
{
    private $key;
    private $value;

    public function __construct($key,$value)
    {
        $this->key = $key===null?'':$key;
        $this->value = $value===null?'':$value;
    }

    public function keep($e) : bool
    {
        return isset($e->tags[$this->key]) && $e->tags[$this->key]===$this->value;
    }

    public function countable() : bool
    {
        return false;
    }
}
