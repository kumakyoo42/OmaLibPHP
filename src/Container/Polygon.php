<?php

namespace Kumakyoo\OmaLib\Container;

use Kumakyoo\OmaLib\OmaReader;
use Kumakyoo\OmaLib\Filter\AndFilter;
use Kumakyoo\OmaLib\Filter\TypeFilter;

class Polygon implements Container
{
    private const DEFAULT_STRIPE_SIZE = 100000;

    private $stripes;
    private $stripeSize;

    public function __construct(...$args)
    {
        if (count($args)==1)
        {
            if (is_string($args[0]))
            {
                $this->stripeSize = self::DEFAULT_STRIPE_SIZE;
                $this->readPolygon($args[0]);
            }
            else if (is_array($args[0]))
            {
                $this->stripeSize = self::DEFAULT_STRIPE_SIZE;
                $this->polygonFromAreas($args[0]);
            }
            else if ($args[0] instanceof Polygon)
            {
                $this->stripeSize = $args[0]->stripeSize;
                $this->stripes = $args[0]->stripes;
            }
            else
              throw new \Exception("wrong parameters for Polygon");
        }
        else if (count($args)==2)
        {
            if (is_string($args[0]))
            {
                $this->stripeSize = $args[1];
                $this->readPolygon($args[0]);
            }
            else if ($args[0] instanceof OmaReader)
            {
                $this->stripeSize = self::DEFAULT_STRIPE_SIZE;
                $this->queryPolygon($args[0],$args[1]);
            }
            else if (is_array($args[0]))
            {
                $this->stripeSize = $args[1];
                $this->polygonFromAreas($args[0]);
            }
            else
              throw new \Exception("wrong parameters for Polygon");
        }
        else if (count($args)==3)
        {
            if ($args[0] instanceof OmaReader)
            {
                $this->stripeSize = $args[2];
                $this->queryPolygon($args[0],$args[1]);
            }
            else
              throw new \Exception("wrong parameters for Polygon");
        }
        else
          throw new \Exception("wrong parameters for Polygon");
    }

    // produces a lot of false positives...
    /** @infection-ignore-all */
    public function getBoundingBox()
    {
        $minlon = false;
        $minlat = false;
        $maxlon = false;
        $maxlat = false;

        foreach ($this->stripes as $stripe)
          foreach ($stripe as $line)
          {
              if ($minlon===false || $line[0]<$minlon)
                $minlon = $line[0];
              if ($minlat===false || $line[1]<$minlat)
                $minlat = $line[1];
              if ($maxlon===false || $line[2]>$maxlon)
                $maxlon = $line[2];
              if ($maxlat===false || $line[3]>$maxlat)
                $maxlat = $line[3];
          }

        return new BoundingBox($minlon,$minlat,$maxlon,$maxlat);
    }

    public function containsPoint($lon, $lat)
    {
        $nr = intval($lat/$this->stripeSize);
        if (!isset($this->stripes[$nr])) return false;

        $inside = false;

        foreach ($this->stripes[$nr] as $l)
        {
            /** @infection-ignore-all */
            if ($l[0]>$lon) break; // this line is for speed only
            if (($l[1]<=$lat) != ($lat<$l[3])) continue;
            // The next line triggers a mutant with infection, which is ok.
            // Due to floating point calculations, the specs do no specify what
            // happends if a point sits exachtly on a line and thus <= instead of <
            // would be correct here too, but would lead to a different result.
            if ($l[0] + ($l[2]-$l[0])*($lat-$l[1])/($l[3]-$l[1]) < $lon)
              $inside = !$inside;
        }

        return $inside;
    }

    public function containsLine($lon, $lat)
    {
        for ($i=0;$i<count($lon);$i++)
          if ($this->containsPoint($lon[$i],$lat[$i])) return true;
        return false;
    }

    public function containsBoundingBox($b)
    {
        return $this->containsPoint($b->getMinlon(),$b->getMinlat())
          || $this->containsPoint($b->getMinlon(),$b->getMaxlat())
          || $this->containsPoint($b->getMaxlon(),$b->getMinlat())
          || $this->containsPoint($b->getMaxlon(),$b->getMaxlat());
    }

    //////////////////////////////////////////////////////////////////

    private function polygonFromAreas($areas)
    {
        $this->stripes = array();

        foreach ($areas as $a)
          $this->addArea($a);

        $this->sortStripes();
    }

    private function queryPolygon($r, $f)
    {
        $save = $r->getFilter();
        $r->reset();
        $r->setFilter(new AndFilter($f,new TypeFilter('A')));

        $this->stripes = array();

        foreach ($r->elements() as $a)
          $this->addArea($a);

        $this->sortStripes();

        $r->reset();
        $r->setFilter($save);
    }

    private function addArea($a)
    {
        $poly = array();
        for ($i=0;$i<count($a->lon);$i++)
          $poly[] = array($a->lon[$i],$a->lat[$i]);
        $this->addStripes($poly);

        for ($j=0;$j<count($a->holes_lon);$j++)
        {
            $poly = array();
            for ($i=0;$i<count($a->holes_lon[$j]);$i++)
              $poly[] = array($a->holes_lon[$j][$i],$a->holes_lat[$j][$i]);
            $this->addStripes($poly);
        }
    }

    private function readPolygon($filename)
    {
        $r = array_reverse(file($filename));

        $this->stripes = array();

        $poly = array();

        while (true)
        {
            if (empty($r)) break;
            $line = array_pop($r);
            if (strlen($line)==0) continue;

            if ($line[0]==' ')
            {
                $tmp = explode(' ',trim($line));
                $poly[] = array($this->conv($tmp[0]),$this->conv($tmp[1]));
            }
            else
            {
                if (count($poly)==0) continue;
                $this->addStripes($poly);
                $poly = array();
            }
        }

        $this->sortStripes();
    }

    private function addStripes($poly)
    {
        for ($i=0;$i<count($poly);$i++)
        {
            $a = $poly[$i];
            $b = $poly[($i+1)%count($poly)];
            if ($a[1]==$b[1]) continue;

            $top = min($a[1],$b[1]);
            $bot = max($a[1],$b[1]);

            $startseg = intval($top/$this->stripeSize);
            $stopseg = intval($bot/$this->stripeSize);
            
            if ($b[0]<$a[0])
            {
                $tmp = $a;
                $a = $b;
                $b = $tmp;
            }

            for ($j=$startseg;$j<=$stopseg;$j++)
            {
                if (!isset($this->stripes[$j]))
                  $this->stripes[$j] = array();
                $this->stripes[$j][] = array($a[0],$a[1],$b[0],$b[1]);
            }
        }
    }

    private function sortStripes()
    {
        foreach ($this->stripes as $k=>$v)
          usort($this->stripes[$k],array('Kumakyoo\OmaLib\Container\Polygon','cmp'));
    }

    private static function cmp($a, $b)
    {
        return $a[0]-$b[0];
    }

    private function conv($s)
    {
        return intval($s*1e7+0.5);
    }
}

?>
