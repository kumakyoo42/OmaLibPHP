<?php

namespace Kumakyoo\OmaLib\Elements;

class Area extends Element
{
    public $lon;
    public $lat;

    public $holes_lon;
    public $holes_lat;

    public function __construct($in, $key, $value)
    {
        parent::__construct($key,$value);

        $count = $in->readSmallInt();
        $this->lon = array();
        $this->lat = array();
        for ($i=0;$i<$count;$i++)
        {
            $this->lon[$i] = $in->readDeltaX();
            $this->lat[$i] = $in->readDeltaY();
        }

        $count = $in->readSmallInt();
        $this->holes_lon = array();
        $this->holes_lat = array();
        for ($i=0;$i<$count;$i++)
        {
            $holeCount = $in->readSmallInt();
            $this->holes_lon[$i] = array();
            $this->holes_lat[$i] = array();
            for ($j=0;$j<$holeCount;$j++)
            {
                $this->holes_lon[$i][$j] = $in->readDeltaX();
                $this->holes_lat[$i][$j] = $in->readDeltaY();
            }
        }
    }

    public function isInside($c) : bool
    {
        return $c->containsLine($this->lon,$this->lat);
    }
}
