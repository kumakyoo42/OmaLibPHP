<?php

namespace Kumakyoo\OmaLib\Filter;

class Filter
{
    public function needsChunk($type, $b) : bool
    {
        return true;
    }

    public function needsBlock($key) : bool
    {
        return true;
    }

    public function needsSlice($value) : bool
    {
        return true;
    }

    public function keep($e) : bool
    {
        return true;
    }

    public function countable() : bool
    {
        return true;
    }
}
