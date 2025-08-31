<?php

namespace Kumakyoo\OmaLib\Filter;

class AndFilter extends Filter
{
    private $fs;

    public function __construct(...$fs)
    {
        $this->fs = $fs;
    }

    public function needsChunk($type, $b) : bool
    {
        foreach ($this->fs as $f)
          if (!$f->needsChunk($type,$b)) return false;
        return true;
    }

    public function needsBlock($key) : bool
    {
        foreach ($this->fs as $f)
          if (!$f->needsBlock($key)) return false;
        return true;
    }

    public function needsSlice($value) : bool
    {
        foreach ($this->fs as $f)
          if (!$f->needsSlice($value)) return false;
        return true;
    }

    public function keep($e) : bool
    {
        foreach ($this->fs as $f)
          if (!$f->keep($e)) return false;
        return true;
    }

    public function countable() : bool
    {
        foreach ($this->fs as $f)
          if (!$f->countable()) return false;
        return true;
    }
}
