<?php

namespace Kumakyoo\OmaLib\Elements;

class Node extends Element
{
    public $lon;
    public $lat;

    public function __construct($in, $key, $value)
    {
        parent::__construct($key,$value);
        $this->lon = $in->readDeltaX();
        $this->lat = $in->readDeltaY();
    }

    public function isInside($c) : bool
    {
        return $c->containsPoint($this->lon,$this->lat);
    }
}
