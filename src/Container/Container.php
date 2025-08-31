<?php

namespace Kumakyoo\OmaLib\Container;

interface Container
{
    public function containsPoint($lon, $lat);
    public function containsLine($lon, $lat);
    public function containsBoundingBox($b);
}

?>
