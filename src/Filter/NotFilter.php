<?php

namespace Kumakyoo\OmaLib\Filter;

class NotFilter
{
    private $f;

    private $c,$b,$s;

    public function __construct($f)
    {
        $this->f = $f;
    }

    public function needsChunk($type, $b) : bool
    {
        $this->c = $this->f->needsChunk($type,$b);
        return true;
    }

    public function needsBlock($key) : bool
    {
        $this->b = $this->f->needsBlock($key);
        return true;
    }

    public function needsSlice($value) : bool
    {
        $this->s = $this->f->needsSlice($value);
        return true;
    }

    public function keep($e) : bool
    {
        return !$this->c || !$this->b || !$this->s || !$this->f->keep($e);
    }

    public function countable() : bool
    {
        return !$this->c || !$this->b || !$this->s;
    }
}
