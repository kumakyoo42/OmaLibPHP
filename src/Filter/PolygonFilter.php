<?php

namespace Kumakyoo\OmaLib\Filter;

use Kumakyoo\OmaLib\Container\BoundingBox;
use Kumakyoo\OmaLib\Container\Polygon;

class PolygonFilter extends BoundingBoxFilter
{
    private $poly;

    public function __construct($arg)
    {
        $this->poly = new Polygon($arg);
        $this->bounds = $this->poly->getBoundingBox();
    }

    public function needsChunk($type, $b) : bool
    {
        $this->cb = $b;
        return $this->bounds->intersects(new BoundingBox($b));
    }

    public function keep($e) : bool
    {
        if (!parent::keep($e)) return false;
        return $e->isInside($this->bounds);
    }

    public function countable() : bool
    {
        return false;
    }
}
