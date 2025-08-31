<?php

namespace Kumakyoo\OmaLib;

use Kumakyoo\OmaLib\Elements\Node;
use Kumakyoo\OmaLib\Elements\Way;
use Kumakyoo\OmaLib\Elements\Area;
use Kumakyoo\OmaLib\Elements\Collection;
use Kumakyoo\OmaLib\Filter\Filter;
use Kumakyoo\OmaLib\Container\BoundingBox;

class OmaReader
{
    const VERSION = 1;

    private $globalBounds;
    private $chunkTable;
    private $blockTable;
    private $sliceTable;
    private $typeTable;

    private $save = null;
    private $generator = null;
    private $filter = null;

    private $chunkFinished = false;
    private $chunk = 0;
    private $blockFinished = false;
    private $block = 0;
    private $sliceFinished = false;
    private $slice = 0;
    private $elementCount = 0;
    private $element = 0;

    private $filename;
    private $in = false;
    private $features = 0;
    private $zipped = false;

    private $key = null;
    private $value = null;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->setFilter(new Filter());

        $this->openFile();
    }

    public function close()
    {
        $this->in->close();
    }

    public function reset()
    {
        $this->chunkFinished = true;
        $this->chunk = -1;
        if ($this->save!==null)
          $this->in = $this->save;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->reset();
    }

    public function getBoundingBox()
    {
        return $this->globalBounds;
    }

    public function containsBlocks($type, $key) : bool
    {
        return isset($this->typeTable[$type][$key]);
    }

    public function containsSlices($type, $key, $value) : bool
    {
        return isset($this->typeTable[$type][$key])
          && in_array($value,$this->typeTable[$type][$key]);
    }

    public function keySet($type) : array|null
    {
        if (!isset($this->typeTable[$type])) return null;
        return array_keys($this->typeTable[$type]);
    }

    public function valueSet($type, $key) : array|null
    {
        if (!isset($this->typeTable[$type][$key])) return null;
        return $this->typeTable[$type][$key];
    }

    public function isZipped() : bool
    {
        return $this->zipped;
    }

    public function containsID() : bool
    {
        return ($this->features&1)!=0;
    }

    public function containsVersion() : bool
    {
        return ($this->features&2)!=0;
    }

    public function containsTimestamp() : bool
    {
        return ($this->features&4)!=0;
    }

    public function containsChangeset() : bool
    {
        return ($this->features&8)!=0;
    }

    public function containsUser() : bool
    {
        return ($this->features&16)!=0;
    }

    public function elementsOnce() : bool
    {
        return ($this->features&32)!=0;
    }

    public function next()
    {
        if ($this->generator===null)
          $this->generator = $this->elements();

        if (!$this->generator->valid()) return null;

        $item = $this->generator->current();
        $this->generator->next();
        return $item;
    }

    public function elements()
    {
        while (true)
        {
            if ($this->chunkFinished)
              if (!$this->readNextChunk())
                return;

            if (!$this->filter->needsChunk($this->chunkTable[$this->chunk]['type'],
                                           $this->chunkTable[$this->chunk]['bounds']))
            {
                $this->chunkFinished = true;
                continue;
            }

            if ($this->blockFinished)
              if (!$this->readNextBlock())
              {
                  $this->chunkFinished = true;
                  continue;
              }

            if (!$this->filter->needsBlock($this->blockTable[$this->block]['key']))
            {
                $this->blockFinished = true;
                continue;
            }

            if ($this->sliceFinished)
              if (!$this->readNextSlice())
              {
                  $this->blockFinished = true;
                  continue;
              }

            if (!$this->filter->needsSlice($this->sliceTable[$this->slice]['value']))
            {
                $this->in = $this->save;
                $this->sliceFinished = true;
                continue;
            }

            $this->element++;
            if ($this->element>=$this->elementCount)
            {
                $this->in = $this->save;
                $this->save = null;
                $this->sliceFinished = true;
                continue;
            }

            $e = $this->readElement();
            if ($this->filter->keep($e))
              yield $e;
        }
    }

    public function count() : int
    {
        $c = 0;

        for ($this->chunk=0;$this->chunk<count($this->chunkTable);$this->chunk++)
        {
            if (!$this->filter->needsChunk($this->chunkTable[$this->chunk]['type'],
                                           $this->chunkTable[$this->chunk]['bounds']))
              continue;

            $this->readChunk();

            for ($this->block=0;$this->block<count($this->blockTable);$this->block++)
            {
                $this->key = $this->blockTable[$this->block]['key'];

                if (!$this->filter->needsBlock($this->key))
                  continue;

                $this->readBlock();

                for ($this->slice=0;$this->slice<count($this->sliceTable);$this->slice++)
                {
                    $this->value = $this->sliceTable[$this->slice]['value'];

                    if (!$this->filter->needsSlice($this->value))
                      continue;

                    $this->readSlice();

                    if ($this->filter->countable())
                      $c += $this->elementCount;
                    else
                      for ($this->element=0;$this->element<$this->elementCount;$this->element++)
                        if ($this->filter->keep($this->readElement()))
                          $c++;

                    $this->in = $this->save;
                    $this->save = null;
                }
            }
        }

        return $c;
    }

    //////////////////////////////////////////////////////////////////

    private function openFile()
    {
        $this->in = new OmaInputStream(fopen($this->filename,'r'));

        $this->enforce($this->in->readByte()==ord('O'), "oma-file expected");
        $this->enforce($this->in->readByte()==ord('M'), "oma-file expected");
        $this->enforce($this->in->readByte()==ord('A'), "oma-file expected");
        $this->enforce($this->in->readByte()==self::VERSION, "oma-file expected");

        $this->features = $this->in->readByte();

        $this->globalBounds = new BoundingBox($this->in);

        $chunkTablePos = $this->in->readLong();
        $this->readHeaderEntries();
        $this->in->seek($chunkTablePos);

        $count = $this->in->readInt();
        $this->chunkTable = array();
        for ($i=0;$i<$count;$i++)
          $this->chunkTable[$i] = array("start"  => $this->in->readLong(),
                                        "type"   => $this->in->readByte(),
                                        "bounds" => new BoundingBox($this->in));
    }

    private function readHeaderEntries()
    {
        while (true)
        {
            $type = $this->in->readByte();
            if ($type<0) $type+=256;
            if ($type==0) break;
            $pos = $this->in->readInt();

            switch ($type&127)
            {
            case ord('c'):
                $name = $this->in->readString();
                if ($name==="DEFLATE")
                  $this->zipped = true;
                else if ($name==="NONE")
                  $this->zipped = false;
                else
                  $this->enforce(false,"unknown compression method: ".$name);
                break;
            case ord('t'):
                $this->readTypeTable($this->zipped && $type==ord('t')+128);
                break;
            default:
                $this->in->seek($pos);
                break;
            }
        }
    }

    private function readTypeTable($zipped)
    {
        $orig = $this->in;
        if ($zipped)
        {
            $len = $this->in->readInt();
            $data = $this->in->readBytes($len);
            $this->in = new OmaInputStream(zlib_decode($data));
        }

        $this->typeTable = array();
        $count = $this->in->readSmallInt();
        for ($i=0;$i<$count;$i++)
        {
            $type = $this->in->readByte();
            $countKeys = $this->in->readSmallInt();

            $keyWithValues = array();

            for ($j=0;$j<$countKeys;$j++)
            {
                $key = $this->in->readString();
                $countValues = $this->in->readSmallInt();

                $values = array();

                for ($k=0;$k<$countValues;$k++)
                {
                    $value = $this->in->readString();
                    $values[] = $value;
                }

                $keyWithValues[$key] = $values;
            }

            $this->typeTable[$type] = $keyWithValues;
        }

        $this->in = $orig;
    }

    private function readNextChunk()
    {
        $this->chunkFinished = false;
        $this->chunk++;
        if ($this->chunk>=count($this->chunkTable)) return false;
        $this->readChunk();
        $this->blockFinished = true;
        $this->block = -1;
        return true;
    }

    private function readChunk()
    {
        $this->in->seek($this->chunkTable[$this->chunk]['start']);
        $blockTablePos = $this->chunkTable[$this->chunk]['start']+$this->in->readInt();
        $this->in->seek($blockTablePos);

        $count = $this->in->readSmallInt();
        $this->blockTable = array();
        for ($i=0;$i<$count;$i++)
          $this->blockTable[$i] = array('start' => $this->chunkTable[$this->chunk]['start']+$this->in->readInt(),
                                        'key'   => $this->in->readString());
    }

    private function readNextBlock()
    {
        $this->blockFinished = false;
        $this->block++;
        if ($this->block>=count($this->blockTable)) return false;
        $this->key = $this->blockTable[$this->block]['key'];

        $this->readBlock();

        $this->sliceFinished = true;
        $this->slice = -1;

        return true;
    }

    private function readBlock()
    {
        $this->in->seek($this->blockTable[$this->block]['start']);
        $sliceTablePos = $this->blockTable[$this->block]['start']+$this->in->readInt();
        $this->in->seek($sliceTablePos);

        $count = $this->in->readSmallInt();
        $this->sliceTable = array();
        for ($i=0;$i<$count;$i++)
          $this->sliceTable[$i] = array('start'=>$this->blockTable[$this->block]['start']+$this->in->readInt(),
                                        'value'=>$this->in->readString());
    }

    private function readNextSlice()
    {
        $this->sliceFinished = false;
        $this->slice++;
        if ($this->slice>=count($this->sliceTable)) return false;
        $this->value = $this->sliceTable[$this->slice]['value'];

        $this->readSlice();
        $this->element = -1;

        return true;
    }

    private function readSlice()
    {
        $this->in->resetDelta();
        $this->in->seek($this->sliceTable[$this->slice]['start']);

        $this->elementCount = $this->in->readInt();
        $this->save = $this->in;
        if ($this->zipped)
        {
            $len = $this->in->readInt();
            $data = $this->in->readBytes($len);
            $this->in = new OmaInputStream(zlib_decode($data));
        }
    }

    private function readElement()
    {
        $e = null;
        switch ($this->chunkTable[$this->chunk]['type'])
        {
         case ord('N'):
            $e = new Node($this->in, $this->key, $this->value);
            break;
         case ord('W'):
            $e = new Way($this->in, $this->key, $this->value);
            break;
         case ord('A'):
            $e = new Area($this->in, $this->key, $this->value);
            break;
         case ord('C'):
            $e = new Collection($this->in, $this->key, $this->value);
            break;
         default:
            /** @infection-ignore-all */
            $this->enforce(false, "unknown element type '".(chr($this->chunkTable[$this->chunk]['type']))."'");
        }

        $e->readTags($this->in);
        $e->readMembers($this->in);
        $e->readMeta($this->in,$this->features|($this->chunkTable[$this->chunk]['type']==ord('C')?1:0));

        return $e;
    }

    private function enforce($value, $msg)
    {
        if ($value===false) throw new \Exception($msg);
    }
}
