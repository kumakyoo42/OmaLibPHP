<?php

namespace Kumakyoo\OmaLib\Filter;

class MemberFilter extends Filter
{
    private $c;
    private $role;

    public function __construct($c,$role=null)
    {
        $this->c = $c;
        $this->role = $role;
    }

    public function keep($e) : bool
    {
        foreach ($e->members as $m)
          if ($m['id']==$this->c->id && ($this->role==null || $this->role===$m['role']))
            return true;
        return false;
    }

    public function countable() : bool
    {
        return false;
    }

}
