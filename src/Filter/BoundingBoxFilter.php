<?php

namespace Kumakyoo\OmaLib\Filter;

use Kumakyoo\OmaLib\Container\BoundingBox;

class BoundingBoxFilter extends Filter
{
    protected $bounds;
    protected $cb;

    public function __construct(...$args)
    {
        $this->bounds = new BoundingBox($args);
    }

    public function needsChunk($type, $b) : bool
    {
        $this->cb = $b;
        return $this->bounds->intersects(new BoundingBox($b));
    }

    public function keep($e) : bool
    {
        return $e->isInside($this->bounds);
    }

    public function countable() : bool
    {
        return $this->bounds->containsBoundingBox(new BoundingBox($this->cb));
    }
}
