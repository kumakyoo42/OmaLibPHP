<?php

namespace Kumakyoo\OmaLib\Filter;

class LifecycleFilter extends Filter
{
    private $lifecycle;

    public function __construct($lifecycle=null)
    {
        $this->lifecycle = $lifecycle;
    }

    public function keep($e) : bool
    {
        if ($this->lifecycle===null)
          return !isset($e->tags['lifecycle']);
        return isset($e->tags['lifecycle']) && $e->tags['lifecycle']===$this->lifecycle;
    }

    public function countable() : bool
    {
        return false;
    }

}
