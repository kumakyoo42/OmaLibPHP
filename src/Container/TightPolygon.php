<?php

namespace Kumakyoo\OmaLib\Container;

use Kumakyoo\OmaLib\OmaInputStream;

class TightPolygon extends Polygon implements Container
{
    public function containsLine($lon, $lat)
    {
        for ($i=0;$i<count($lon);$i++)
          if (!$this->containsPoint($lon[$i],$lat[$i])) return false;
        return true;
    }

    public function containsBoundingBox($b)
    {
        return $this->containsPoint($b->getMinlon(),$b->getMinlat())
          && $this->containsPoint($b->getMinlon(),$b->getMaxlat())
          && $this->containsPoint($b->getMaxlon(),$b->getMinlat())
          && $this->containsPoint($b->getMaxlon(),$b->getMaxlat());
    }
}

?>
