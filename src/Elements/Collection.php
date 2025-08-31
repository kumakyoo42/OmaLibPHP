<?php

namespace Kumakyoo\OmaLib\Elements;

use Kumakyoo\OmaLib\Container\BoundingBox;

class Collection extends Element
{
    public $defs;

    public function __construct($in, $key, $value)
    {
        parent::__construct($key,$value);

        $count = $in->readSmallInt();
        $this->defs = array();

        /** @infection-ignore-all */
        for ($i=0;$i<$count;$i++)
          $this->defs[$i] = array('type'   => $in->readByte(),
                                  'bounds' => new BoundingBox($in),
                                  'key'    => $in->readString(),
                                  'value'  => $in->readString());
    }

    public function isInside($c) : bool
    {
        if (count($this->defs)==0) return true;

        /** @infection-ignore-all */
        foreach ($this->defs as $def)
          if ($c->containsBoundingBox($def['bounds']))
              return true;

        /** @infection-ignore-all */
        return false;
    }
}
