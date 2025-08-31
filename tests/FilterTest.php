<?php

namespace Kumakyoo\OmaLib\Tests;

use PHPUnit\Framework\TestCase;
use Kumakyoo\OmaLib\OmaInputStream;
use Kumakyoo\OmaLib\Filter\Filter;
use Kumakyoo\OmaLib\Filter\IDFilter;
use Kumakyoo\OmaLib\Filter\VersionFilter;
use Kumakyoo\OmaLib\Filter\TimestampFilter;
use Kumakyoo\OmaLib\Filter\ChangesetFilter;
use Kumakyoo\OmaLib\Filter\UserFilter;
use Kumakyoo\OmaLib\Filter\TypeFilter;
use Kumakyoo\OmaLib\Filter\KeyFilter;
use Kumakyoo\OmaLib\Filter\TagFilter;
use Kumakyoo\OmaLib\Filter\BlockFilter;
use Kumakyoo\OmaLib\Filter\BlockSliceFilter;
use Kumakyoo\OmaLib\Filter\MemberFilter;
use Kumakyoo\OmaLib\Filter\LifecycleFilter;
use Kumakyoo\OmaLib\Filter\AndFilter;
use Kumakyoo\OmaLib\Filter\OrFilter;
use Kumakyoo\OmaLib\Filter\NotFilter;
use Kumakyoo\OmaLib\Filter\BoundingBoxFilter;
use Kumakyoo\OmaLib\Filter\PolygonFilter;
use Kumakyoo\OmaLib\Elements\Node;
use Kumakyoo\OmaLib\Elements\Way;
use Kumakyoo\OmaLib\Elements\Area;
use Kumakyoo\OmaLib\Elements\Collection;
use Kumakyoo\OmaLib\Container\Polygon;

class FilterTest extends TestCase
{
    public function testFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00");
        $e = new Node($ois,'highway','footway');

        $f = new Filter();
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e));
        self::assertTrue($f->countable());
    }

    public function testIDFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x02");
        $e1 = new Node($ois,'highway','footway');
        $e1->readMeta($ois,1);
        $e2 = new Node($ois,'highway','footway');
        $e2->readMeta($ois,1);

        $f = new IDFilter(1);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());
    }

    public function testVersionFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x01".
                                  "\x00\x00\x00\x00\x02");
        $e1 = new Node($ois,'highway','footway');
        $e1->readMeta($ois,2);
        $e2 = new Node($ois,'highway','footway');
        $e2->readMeta($ois,2);

        $f = new VersionFilter(1);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());
    }

    public function testTimestampFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xa2".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xa4".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xb0".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xb7".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xbb"
                                  );

        for ($i=0;$i<5;$i++)
        {
            $e[$i] = new Node($ois,'highway','footway');
            $e[$i]->readMeta($ois,4);
        }

        $f = new TimestampFilter(1698580919);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e[0]));
        self::assertFalse($f->keep($e[1]));
        self::assertFalse($f->keep($e[2]));
        self::assertTrue($f->keep($e[3]));
        self::assertFalse($f->keep($e[4]));
        self::assertFalse($f->countable());

        $f = new TimestampFilter(1698580900,1698580919);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e[0]));
        self::assertTrue($f->keep($e[1]));
        self::assertTrue($f->keep($e[2]));
        self::assertTrue($f->keep($e[3]));
        self::assertFalse($f->keep($e[4]));
        self::assertFalse($f->countable());
    }

    public function testChangesetFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xa2".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xa4".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xb0".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xb7".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x65\x3e\x49\xbb"
                                  );

        for ($i=0;$i<5;$i++)
        {
            $e[$i] = new Node($ois,'highway','footway');
            $e[$i]->readMeta($ois,8);
        }

        $f = new ChangesetFilter(1698580919);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e[0]));
        self::assertFalse($f->keep($e[1]));
        self::assertFalse($f->keep($e[2]));
        self::assertTrue($f->keep($e[3]));
        self::assertFalse($f->keep($e[4]));
        self::assertFalse($f->countable());

        $f = new ChangesetFilter(1698580900,1698580919);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e[0]));
        self::assertTrue($f->keep($e[1]));
        self::assertTrue($f->keep($e[2]));
        self::assertTrue($f->keep($e[3]));
        self::assertFalse($f->keep($e[4]));
        self::assertFalse($f->countable());
    }

    public function testUserFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x00\x00\x00\x42\x01A".
                                  "\x00\x00\x00\x00\x00\x00\x00\x43\x01B".
                                  "\x00\x00\x00\x00\x00\x00\x00\x44\x00");
        $e1 = new Node($ois,'highway','footway');
        $e1->readMeta($ois,16);
        $e2 = new Node($ois,'highway','footway');
        $e2->readMeta($ois,16);
        $e3 = new Node($ois,'highway','footway');
        $e3->readMeta($ois,16);

        $f = new UserFilter(66);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new UserFilter(67);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new UserFilter('A');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->keep($e3));
        self::assertFalse($f->countable());

        $f = new UserFilter('B');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->keep($e3));
        self::assertFalse($f->countable());

        $f = new UserFilter(null);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertTrue($f->keep($e3));
        self::assertFalse($f->countable());
    }

    public function testTypeFilter()
    {
        $f = new TypeFilter(67);
        self::assertTrue($f->needsChunk(ord('C'),[0,0,0,0]));
        self::assertFalse($f->needsChunk(ord('B'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep(null));
        self::assertTrue($f->countable());

        $f = new TypeFilter('C');
        self::assertTrue($f->needsChunk(ord('C'),[0,0,0,0]));
        self::assertFalse($f->needsChunk(ord('B'),[0,0,0,0]));

        $f = new TypeFilter("ACE");
        self::assertTrue($f->needsChunk(ord('C'),[0,0,0,0]));
        self::assertFalse($f->needsChunk(ord('B'),[0,0,0,0]));

        $f = new TypeFilter([67,68]);
        self::assertTrue($f->needsChunk(ord('C'),[0,0,0,0]));
        self::assertFalse($f->needsChunk(ord('B'),[0,0,0,0]));
    }

    public function testKeyFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x02\x01A\x01B\x01C\x01D".
                                  "\x00\x00\x00\x00\x03\x01E\x01F\x01G\x01H\x00\x00");
        $e1 = new Node($ois,'highway','footway');
        $e1->readTags($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readTags($ois);

        $f = new KeyFilter('A');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new KeyFilter('G');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new KeyFilter(null);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->countable());
    }

    public function testTagFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x02\x01A\x01B\x01C\x01D".
                                  "\x00\x00\x00\x00\x03\x01E\x01F\x01G\x01H\x00\x00");
        $e1 = new Node($ois,'highway','footway');
        $e1->readTags($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readTags($ois);

        $f = new TagFilter('A','B');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new TagFilter('G','H');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new TagFilter(null,null);
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->countable());

        $f = new TagFilter('A','D');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->countable());
    }

    public function testBlockFilter()
    {
        $f = new BlockFilter('highway');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep(null));
        self::assertTrue($f->countable());

        $f = new BlockFilter('natural');
        self::assertFalse($f->needsBlock('highway'));
        self::assertTrue($f->needsBlock('natural'));
    }

    public function testBlockSliceFilter()
    {
        $f = new BlockSliceFilter('highway','footway');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->needsSlice('tree'));
        self::assertTrue($f->keep(null));
        self::assertTrue($f->countable());

        $f = new BlockSliceFilter('natural','tree');
        self::assertFalse($f->needsBlock('highway'));
        self::assertTrue($f->needsBlock('natural'));
        self::assertFalse($f->needsSlice('footway'));
        self::assertTrue($f->needsSlice('tree'));
    }

    public function testMemberFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00\x00\x01\x01A\x00".
                                  "\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00\x00\x01\x01B\x00".
                                  "\x00\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00\x00\x02\x01A\x00".
                                  "\x00\x00\x00\x00\x00\x00\x00\x00\x01");
        $e1 = new Node($ois,'highway','footway');
        $e1->readMembers($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readMembers($ois);
        $e3 = new Node($ois,'highway','footway');
        $e3->readMembers($ois);
        $c = new Collection($ois,'highway','footway');
        $c->readMeta($ois,1);

        $f = new MemberFilter($c,'A');
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->keep($e3));
        self::assertFalse($f->countable());

        $f = new MemberFilter($c,'B');
        self::assertFalse($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->keep($e3));
    }

    public function testLifecycleFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x02\x09lifecycle\x04used\x01C\x01D".
                                  "\x00\x00\x00\x00\x02\x09lifecycle\x03not\x01C\x01D".
                                  "\x00\x00\x00\x00\x03\x01E\x01F\x01G\x01H\x00\x00");
        $e1 = new Node($ois,'highway','footway');
        $e1->readTags($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readTags($ois);
        $e3 = new Node($ois,'highway','footway');
        $e3->readTags($ois);

        $f = new LifecycleFilter();
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertTrue($f->keep($e3));
        self::assertFalse($f->countable());

        $f = new LifecycleFilter('used');
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->keep($e3));
    }

    public function testAndFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x01\x01A\x01B".
                                  "\x00\x00\x00\x00\x01\x01C\x01D".
                                  "\x00\x00\x00\x00\x01\x01A\x01B".
                                  "\x00\x00\x00\x00\x01\x01C\x01D".
                                  "\x00\x01\x01A\x01B".
                                  "\x00\x01\x01C\x01D".
                                  "\x00\x01\x01A\x01B".
                                  "\x00\x01\x01C\x01D");
        $e1 = new Node($ois,'highway','footway');
        $e1->readTags($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readTags($ois);
        $e3 = new Node($ois,'natural','tree');
        $e3->readTags($ois);
        $e4 = new Node($ois,'natural','tree');
        $e4->readTags($ois);
        $e5 = new Way($ois,'highway','footway');
        $e5->readTags($ois);
        $e6 = new Way($ois,'highway','footway');
        $e6->readTags($ois);
        $e7 = new Way($ois,'natural','tree');
        $e7->readTags($ois);
        $e8 = new Way($ois,'natural','tree');
        $e8->readTags($ois);

        $f = new AndFilter(new TypeFilter('N'),new BlockFilter('highway'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertFalse($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertTrue($f->keep($e8));

        $f = new AndFilter(new TypeFilter('N'),new BlockSliceFilter('highway','tree'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertFalse($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertFalse($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertFalse($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertTrue($f->keep($e8));

        $f = new AndFilter(new TypeFilter('N'),new BlockFilter('highway'),new TagFilter('A','B'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertFalse($f->keep($e4));
        self::assertFalse($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertFalse($f->keep($e6));
        self::assertFalse($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertFalse($f->keep($e8));

        $f = new AndFilter();
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertTrue($f->keep($e8));

        $f = new AndFilter(new TagFilter('A','B'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertFalse($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertFalse($f->keep($e6));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertFalse($f->keep($e8));
    }

    public function testOrFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00\x01\x01A\x01B".
                                  "\x00\x00\x00\x00\x01\x01C\x01D".
                                  "\x00\x00\x00\x00\x01\x01A\x01B".
                                  "\x00\x00\x00\x00\x01\x01C\x01D".
                                  "\x00\x01\x01A\x01B".
                                  "\x00\x01\x01C\x01D".
                                  "\x00\x01\x01A\x01B".
                                  "\x00\x01\x01C\x01D");
        $e1 = new Node($ois,'highway','footway');
        $e1->readTags($ois);
        $e2 = new Node($ois,'highway','footway');
        $e2->readTags($ois);
        $e3 = new Node($ois,'natural','tree');
        $e3->readTags($ois);
        $e4 = new Node($ois,'natural','tree');
        $e4->readTags($ois);
        $e5 = new Way($ois,'highway','footway');
        $e5->readTags($ois);
        $e6 = new Way($ois,'highway','footway');
        $e6->readTags($ois);
        $e7 = new Way($ois,'natural','tree');
        $e7->readTags($ois);
        $e8 = new Way($ois,'natural','tree');
        $e8->readTags($ois);

        $f = new OrFilter(new TypeFilter('N'),new BlockFilter('highway'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertFalse($f->needsBlock('natural'));
        self::assertFalse($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertFalse($f->keep($e7));
        self::assertFalse($f->keep($e8));

        $f = new OrFilter(new TypeFilter('N'),new BlockFilter('highway'),new TagFilter('A','B'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertFalse($f->keep($e8));

        $f = new OrFilter();
        self::assertFalse($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertFalse($f->needsBlock('highway'));
        self::assertFalse($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertFalse($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertFalse($f->needsBlock('natural'));
        self::assertFalse($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertFalse($f->keep($e3));
        self::assertFalse($f->keep($e4));
        self::assertFalse($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertFalse($f->needsBlock('highway'));
        self::assertFalse($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertFalse($f->keep($e5));
        self::assertFalse($f->keep($e6));
        self::assertFalse($f->needsBlock('natural'));
        self::assertFalse($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertFalse($f->keep($e7));
        self::assertFalse($f->keep($e8));

        $f = new OrFilter(new TagFilter('A','B'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertFalse($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertFalse($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertFalse($f->keep($e6));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertFalse($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertFalse($f->keep($e8));

        $f = new OrFilter(new BlockFilter('highway'), new BlockFilter('natural'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e1));
        self::assertTrue($f->keep($e2));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e3));
        self::assertTrue($f->keep($e4));
        self::assertTrue($f->needsChunk(ord('W'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e5));
        self::assertTrue($f->keep($e6));
        self::assertTrue($f->needsBlock('natural'));
        self::assertTrue($f->needsSlice('tree'));
        self::assertTrue($f->countable());
        self::assertTrue($f->keep($e7));
        self::assertTrue($f->keep($e8));
    }

    public function testNotFilter()
    {
        $ois = new OmaInputStream("\x00\x00\x00\x00");
        $e = new Node($ois,'highway','footway');

        $f = new NotFilter(new Filter());
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($e));
        self::assertFalse($f->countable());

        $f = new NotFilter(new TypeFilter('W'));
        self::assertTrue($f->needsChunk(ord('N'),[0,0,0,0]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($e));
        self::assertTrue($f->countable());
    }

    public function testBoundingBoxFilter()
    {
        $ois = new OmaInputStream("\x80\x00\x04\x06\xfe\x23\x80\x00\x10\x20\x30\x40".
                                  "\x02\xff\xff\xff\xff\xff\xff\x00\x10".
                                  "\x03\xff\xfe\xff\xf2\x00\x01\x00\x03\xff\xfe\x00\x04\x00".
                                  "\x00");
        $n = new Node($ois,'highway','footway');
        $w = new Way($ois,'highway','footway');
        $a = new Area($ois,'highway','footway');
        $c = new Collection($ois,'highway','footway');

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567000,270544800,67567300,270545100]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($n));
        self::assertTrue($f->keep($w));
        self::assertTrue($f->keep($a));
        self::assertTrue($f->keep($c));
        self::assertFalse($f->countable());

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567140,270544940,67567160,270544960]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($n));
        self::assertTrue($f->keep($w));
        self::assertTrue($f->keep($a));
        self::assertTrue($f->keep($c));
        self::assertTrue($f->countable());

        $f = new BoundingBoxFilter(67567250,270545000,67567400,270545200);
        self::assertTrue($f->needsChunk(ord('N'),[67567000,270544800,67567300,270545100]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($n));
        self::assertFalse($f->keep($w));
        self::assertFalse($f->keep($a));
        self::assertTrue($f->keep($c));
        self::assertFalse($f->countable());

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertFalse($f->needsChunk(ord('N'),[66567140,270544940,66567160,270544960]));

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567000,270544940,67567100,270544960]));

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567200,270544940,67567250,270544960]));

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567140,270544800,67567160,270544900]));

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567140,270545000,67567160,270545100]));

        $f = new BoundingBoxFilter(67567100,270544900,67567200,270545000);
        self::assertTrue($f->needsChunk(ord('N'),[67567050,270544850,67567150,270544950]));
    }

    public function testPolygonFilter()
    {
        $ois = new OmaInputStream("\x80\x00\x00\x04\x06\xfe\x80\x00\x10\x20\x30\x40".
                                  "\x02\xff\xff\xff\xff\xff\xff\x00\x10".
                                  "\x03\xff\xfe\xff\xf2\x00\x01\x00\x03\xff\xfe\x00\x04\x00".
                                  "\x00");
        $n = new Node($ois,'highway','footway');
        $w = new Way($ois,'highway','footway');
        $a = new Area($ois,'highway','footway');
        $c = new Collection($ois,'highway','footway');

        $f = new PolygonFilter(new Polygon("tests/testdata/pf1.poly"));
        self::assertTrue($f->needsChunk(ord('N'),[200000,270000000,300000,280000000]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertTrue($f->keep($n));
        self::assertTrue($f->keep($w));
        self::assertTrue($f->keep($a));
        self::assertTrue($f->keep($c));
        self::assertFalse($f->countable());

        $f = new PolygonFilter(new Polygon("tests/testdata/pf2.poly"));
        self::assertTrue($f->needsChunk(ord('N'),[2000000,270000000,3000000,280000000]));
        self::assertTrue($f->needsBlock('highway'));
        self::assertTrue($f->needsSlice('footway'));
        self::assertFalse($f->keep($n));
        self::assertFalse($f->keep($w));
        self::assertFalse($f->keep($a));
        self::assertTrue($f->keep($c));
        self::assertFalse($f->countable());

        $f = new PolygonFilter(new Polygon("tests/testdata/pf2.poly"));
        self::assertFalse($f->needsChunk(ord('N'),[200000,270000000,300000,280000000]));
    }
}
