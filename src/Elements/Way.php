<?php

namespace Kumakyoo\OmaLib\Elements;

class Way extends Element
{
    public $lon;
    public $lat;

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
    }

    public function isInside($c) : bool
    {
        return $c->containsLine($this->lon,$this->lat);
    }
}
