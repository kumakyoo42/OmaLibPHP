<?php

namespace Kumakyoo\OmaLib\Container;

use Kumakyoo\OmaLib\OmaInputStream;

class TightBoundingBox extends BoundingBox implements Container
{
    public function containsLine($lon, $lat)
    {
        for ($i=0;$i<count($lon);$i++)
          if (!$this->containsPoint($lon[$i],$lat[$i])) return false;
        return true;
    }
}

?>
