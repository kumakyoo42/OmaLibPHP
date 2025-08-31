<?php

namespace Kumakyoo\OmaLib\Filter;

class ChangesetFilter extends Filter
{
    private $start, $end;

    public function __construct($start, $end=false)
    {
        if ($end===false)
          $this->start = $this->end = $start;
        else
        {
            $this->start = $start;
            $this->end = $end;
        }
    }

    public function keep($e) : bool
    {
        return $e->changeset>=$this->start && $e->changeset<=$this->end;
    }

    public function countable() : bool
    {
        return false;
    }
}
