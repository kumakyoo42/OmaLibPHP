<?php

namespace Kumakyoo\OmaLib\Container;

use Kumakyoo\OmaLib\OmaInputStream;

class BoundingBox implements Container
{
    private $minlon;
    private $minlat;
    private $maxlon;
    private $maxlat;

    public function __construct(...$args)
    {
        if (count($args)==1)
        {
            if ($args[0] instanceof OmaInputStream)
            {
                $this->minlon = $args[0]->readInt();
                $this->minlat = $args[0]->readInt();
                $this->maxlon = $args[0]->readInt();
                $this->maxlat = $args[0]->readInt();
            }
            else if ($args[0] instanceof BoundingBox)
            {
                $this->minlon = $args[0]->minlon;
                $this->minlat = $args[0]->minlat;
                $this->maxlon = $args[0]->maxlon;
                $this->maxlat = $args[0]->maxlat;
            }
            else if (is_array($args[0]))
            {
                $this->minlon = $args[0][0];
                $this->minlat = $args[0][1];
                $this->maxlon = $args[0][2];
                $this->maxlat = $args[0][3];
            }
            else
              throw new \Exception("wrong parameters for BoundingBox");
        }
        else if (count($args)==4)
        {
            $this->minlon = $args[0];
            $this->minlat = $args[1];
            $this->maxlon = $args[2];
            $this->maxlat = $args[3];
        }
        else
          throw new \Exception("wrong parameters for BoundingBox");
    }

    public function getMinLon()
    {
        return $this->minlon;
    }

    public function getMinLat()
    {
        return $this->minlat;
    }

    public function getMaxLon()
    {
        return $this->maxlon;
    }

    public function getMaxLat()
    {
        return $this->maxlat;
    }
    public function containsPoint($lon, $lat)
    {
        return $this->minlon==0x7fffffff || ($lon>=$this->minlon && $lon<=$this->maxlon && $lat>=$this->minlat && $lat<=$this->maxlat);
    }

    public function containsLine($lon, $lat)
    {
        for ($i=0;$i<count($lon);$i++)
          if ($this->containsPoint($lon[$i],$lat[$i])) return true;
        return false;
    }

    public function containsBoundingBox($b)
    {
        return $this->containsPoint($b->minlon,$b->minlat) && $this->containsPoint($b->maxlon,$b->maxlat);
    }

    public function intersects($b)
    {
        if ($b->minlon==0x7fffffff || $this->minlon==0x7fffffff) return true;
        return $b->maxlon>=$this->minlon && $b->minlon<=$this->maxlon
          && $b->maxlat>=$this->minlat && $b->minlat<=$this->maxlat;
    }
}

?>
