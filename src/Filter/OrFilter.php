<?php

namespace Kumakyoo\OmaLib\Filter;

class OrFilter extends Filter
{
    private $fs;

    private $c, $b, $s;

    public function __construct(...$fs)
    {
        $this->fs = $fs;
        /** @infection-ignore-all */
        $this->c = array_fill(false,0,count($fs));
        /** @infection-ignore-all */
        $this->b = array_fill(false,0,count($fs));
        /** @infection-ignore-all */
        $this->s = array_fill(false,0,count($fs));
    }

    public function needsChunk($type, $b) : bool
    {
        foreach ($this->fs as $i=>$f)
          $this->c[$i] = $f->needsChunk($type,$b);
        foreach ($this->c as $v)
          if ($v) return true;
        return false;
    }

    public function needsBlock($key) : bool
    {
        foreach ($this->fs as $i=>$f)
          $this->b[$i] = $this->c[$i] && $f->needsBlock($key);
        foreach ($this->b as $v)
          if ($v) return true;
        return false;
    }

    public function needsSlice($value) : bool
    {
        foreach ($this->fs as $i=>$f)
          $this->s[$i] = $this->b[$i] && $f->needsSlice($value);
        foreach ($this->s as $v)
          if ($v) return true;
        return false;
    }

    public function keep($e) : bool
    {
        foreach ($this->fs as $i=>$f)
          if ($this->s[$i] && $f->keep($e)) return true;
        return false;
    }

    public function countable() : bool
    {
        foreach ($this->fs as $i=>$f)
          if ($this->s[$i] && !$f->countable()) return false;
        return true;
    }
}
