<?php

namespace Kumakyoo\OmaLib;

class OmaInputStream
{
    private $in = null;
    private $pos;

    private $lastx;
    private $lasty;

    public function __construct($input)
    {
        if (is_resource($input) || is_string($input))
          $this->in = $input;
        else
          throw new \Exception("cannot initialize OmaInputStream");

        $this->pos = 0;
        /** @infection-ignore-all */
        $this->resetDelta();
    }

    public function close()
    {
        if (is_resource($this->in))
          fclose($this->in);
        $this->in = null;
    }

    private function readUnsignedByte() : int
    {
        if (is_string($this->in))
        {
            if ($this->pos>=strlen($this->in)) throw new \Exception('end of OmaInputStream reached');
            return ord($this->in[$this->pos++]);
        }
        else if (is_resource($this->in))
        {
            $h = fread($this->in,1);
            if (strlen($h)==0) throw new \Exception('end of OmaInputStream reached');
            return ord($h[0]);
        }
        else throw new \Exception('OmaInputStream closed');
    }

    public function readByte() : int
    {
        $h = $this->readUnsignedByte();
        return $h>=128?$h-256:$h;
    }

    private function readUnsignedShort() : int
    {
        return ($this->readUnsignedByte()<<8) + $this->readUnsignedByte();
    }

    public function readShort() : int
    {
        $h = $this->readUnsignedShort();
        return $h>=32768?$h-65536:$h;
    }

    private function readUnsignedInt() : int
    {
        return ($this->readUnsignedByte()<<24)
          + ($this->readUnsignedByte()<<16)
          + ($this->readUnsignedByte()<<8)
          + $this->readUnsignedByte();
    }

    public function readInt() : int
    {
        $h = $this->readUnsignedInt();
        return $h>=2147483648?$h-4294967296:$h;
    }

    public function readLong() : int
    {
        $h1 = ($this->readUnsignedByte()<<24)
          + ($this->readUnsignedByte()<<16)
          + ($this->readUnsignedByte()<<8)
          + $this->readUnsignedByte();
        $h2 = ($this->readUnsignedByte()<<24)
          + ($this->readUnsignedByte()<<16)
          + ($this->readUnsignedByte()<<8)
          + $this->readUnsignedByte();
        return ($h1<<32) + $h2; // This turns automatically into a negative number if too large...
    }

    public function readSmallInt() : int
    {
        $val = $this->readUnsignedByte();
        if ($val<255) return $val;
        $val = $this->readUnsignedShort();
        if ($val<65535) return $val;
        return $this->readUnsignedInt();
    }

    public function readString() : string
    {
        $len = $this->readSmallInt();
        return $len==0?'':$this->readBytes($len);
    }

    public function seek($pos)
    {
        if ($this->in===null) return;
        if (is_string($this->in))
          $this->pos = $pos;
        else
          fseek($this->in,$pos);
    }

    public function readBytes($len) : string
    {
        if (is_string($this->in))
        {
            $ret = "";
            for ($i=0;$i<$len;$i++)
              $ret .= chr($this->readByte());
            return $ret;
        }
        return fread($this->in,$len);
    }

    public function resetDelta()
    {
        $this->lastx = $this->lasty = 0;
    }

    public function readDeltaX() : int
    {
        $this->lastx = $this->delta($this->lastx);
        return $this->lastx;
    }

    public function readDeltaY() : int
    {
        $this->lasty = $this->delta($this->lasty);
        return $this->lasty;
    }

    private function delta($last)
    {
        $delta = $this->readShort();
        return $delta==-32768?$this->readInt():($last+$delta);
    }
}
