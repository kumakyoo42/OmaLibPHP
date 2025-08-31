<?php

namespace Kumakyoo\OmaLib\Filter;

class TypeFilter extends Filter
{
    private $types;

    public function __construct($types)
    {
        if (is_numeric($types))
          $this->types = [$types];
        else if (is_string($types))
        {
            $this->types = array();
            /** @infection-ignore-all */
            for ($i=0;$i<strlen($types);$i++)
              $this->types[] = ord($types[$i]);
        }
        else
          $this->types = $types;
    }

    public function needsChunk($type, $b) : bool
    {
        foreach ($this->types as $t)
          if ($t==$type)
            return true;

        return false;
    }
}
