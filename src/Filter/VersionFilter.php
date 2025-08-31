<?php

namespace Kumakyoo\OmaLib\Filter;

class VersionFilter extends Filter
{
    private $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function keep($e) : bool
    {
        return $e->version==$this->version;
    }

    public function countable() : bool
    {
        return false;
    }
}
