<?php

namespace Kumakyoo\OmaLib\Elements;

abstract class Element
{
    public $id = false;
    public $version = false;
    public $timestamp = false;
    public $changeset = false;
    public $uid = false;
    public $user = false;

    public $key;
    public $value;

    public $tags = array();
    public $members = array();

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function readTags($in)
    {
        $this->tags = array();
        $count = $in->readSmallInt();
        for ($i=0;$i<$count;$i++)
          $this->tags[$in->readString()] = $in->readString();
    }

    public function readMembers($in)
    {
        $this->members = array();
        $count = $in->readSmallInt();
        for ($i=0;$i<$count;$i++)
          $this->members[$i] = array('id'   => $in->readLong(),
                                     'role' => $in->readString(),
                                     'nr'   => $in->readSmallInt());
    }

    public function readMeta($in, $features)
    {
        if (($features&1)!=0)
          $this->id = $in->readLong();
        if (($features&2)!=0)
          $this->version = $in->readSmallInt();
        if (($features&4)!=0)
          $this->timestamp = $in->readLong();
        if (($features&8)!=0)
          $this->changeset = $in->readLong();
        if (($features&16)!=0)
        {
            $this->uid = $in->readInt();
            $this->user = $in->readString();
        }
    }

    abstract public function isInside($c) : bool;
}
