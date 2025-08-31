<?php

namespace Kumakyoo\OmaLib\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Kumakyoo\OmaLib\OmaReader;
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
use Kumakyoo\OmaLib\Container\BoundingBox;

class OmaReaderTest extends TestCase
{
    public function testExample()
    {
        $r = new OmaReader('tests/testdata/example.oma');
        self::assertEquals(new BoundingBox(78687201,479997914,78690999,480000241),$r->getBoundingBox());
        self::assertTrue($r->isZipped());
        self::assertTrue($r->containsID());
        self::assertFalse($r->containsVersion());
        self::assertTrue($r->containsTimestamp());
        self::assertFalse($r->containsChangeset());
        self::assertFalse($r->containsUser());
        self::assertFalse($r->elementsOnce());

        self::assertTrue($r->containsBlocks(ord('W'),'highway'));
        self::assertFalse($r->containsBlocks(ord('W'),'leisure'));
        self::assertFalse($r->containsBlocks(ord('X'),'highway'));
        self::assertTrue($r->containsSlices(ord('W'),'highway','service'));
        self::assertFalse($r->containsSlices(ord('X'),'highway','service'));
        self::assertFalse($r->containsSlices(ord('W'),'leisure','service'));
        self::assertFalse($r->containsSlices(ord('W'),'highway','primary'));

        self::assertSame($r->keySet(ord('X')),null);
        self::assertEqualsCanonicalizing($r->keySet(ord('N')),['natural','tourism']);

        self::assertSame($r->valueSet(ord('X'),'natural'),null);
        self::assertSame($r->valueSet(ord('N'),'highway'),null);
        self::assertEqualsCanonicalizing($r->valueSet(ord('N'),'natural'),['tree','peak','spring']);

        foreach ($this->exampleElements() as $data)
        {
            $el = $r->next();

            if ($data==null)
            {
                self::assertSame($el,null);
                continue;
            }

            self::assertSame($data['type'],      get_class($el));
            self::assertSame($data['key'],       $el->key);
            self::assertSame($data['value'],     $el->value);
            self::assertSame($data['id'],        $el->id);
            self::assertSame($data['timestamp'], $el->timestamp);
            self::assertSame($data['version'],   $el->version);
            self::assertSame($data['changeset'], $el->changeset);
            self::assertSame($data['uid'],       $el->uid);
            self::assertSame($data['user'],      $el->user);
            self::assertEqualsCanonicalizing($data['tags'],    $el->tags);
            self::assertEqualsCanonicalizing($data['members'], $el->members);

            switch ($data['type'])
            {
             case 'Kumakyoo\OmaLib\Elements\Node':
                self::assertSame($data['lon'], $el->lon);
                self::assertSame($data['lat'], $el->lat);
                break;
             case 'Kumakyoo\OmaLib\Elements\Way':
                self::assertCount(count($data['lon']),$el->lon);
                self::assertCount(count($data['lat']),$el->lat);
                for ($i=0;$i<count($data['lon']);$i++)
                {
                    self::assertSame($data['lon'][$i], $el->lon[$i]);
                    self::assertSame($data['lat'][$i], $el->lat[$i]);
                }
                break;
             case 'Kumakyoo\OmaLib\Elements\Area':
                self::assertCount(count($data['lon']),$el->lon);
                self::assertCount(count($data['lat']),$el->lat);
                for ($i=0;$i<count($data['lon']);$i++)
                {
                    self::assertSame($data['lon'][$i], $el->lon[$i]);
                    self::assertSame($data['lat'][$i], $el->lat[$i]);
                }
                self::assertCount(count($data['holes_lon']),$el->holes_lon);
                self::assertCount(count($data['holes_lat']),$el->holes_lat);
                for ($i=0;$i<count($data['holes_lon']);$i++)
                {
                    self::assertCount(count($data['holes_lon'][$i]),$el->holes_lon[$i]);
                    self::assertCount(count($data['holes_lat'][$i]),$el->holes_lat[$i]);
                    for ($j=0;$j<count($data['holes_lon'][$i]);$j++)
                    {
                        self::assertSame($data['holes_lon'][$i][$j], $el->holes_lon[$i][$j]);
                        self::assertSame($data['holes_lat'][$i][$j], $el->holes_lat[$i][$j]);
                    }
                }
                break;
             case 'Kumakyoo\OmaLib\Elements\Collection':
                self::assertCount(count($data['defs']),$el->defs);
                // later, when implemented: further tests
                break;
            }
        }

        $r->reset();
        self::assertSame(12,$r->count());

        $r->reset();
        $c = 0;
        foreach ($r->elements() as $v)
          $c++;
        self::assertSame(12,$c);

        $r->reset();
        $r->next();
        $r->reset();
        self::assertSame(12,$r->count());

        $r->close();
    }

    public function exampleElements()
    {
        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "tree",
               "tags" => array("natural"=>"tree"),
               "members" => array(),
               "id" => 25469,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 78687752,
               "lat" => 479999830,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "tree",
               "tags" => array("natural"=>"tree",
                               "leave_cycle"=>"evergreen",
                               "denotation"=>"natural_monument",
                               "leaf_type"=>"needleleaved"),
               "members" => array(),
               "id" => 25482,
               "timestamp" => 1698580919,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 78688278,
               "lat" => 479998736,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "tree",
               "tags" => array("natural"=>"tree"),
               "members" => array(),
               "id" => 25487,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 78689638,
               "lat" => 479999281,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "",
               "tags" => array("natural"=>"rock"),
               "members" => array(),
               "id" => 25471,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 78688745,
               "lat" => 479999668,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "tourism",
               "value" => "information",
               "tags" => array("tourism"=>"information",
                               "information"=>"guidepost"),
               "members" => array(array("id"=>64,"role"=>"guidepost","nr"=>3)),
               "id" => 25474,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 78688409,
               "lat" => 479999250,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Area",
               "key" => "natural",
               "value" => "water",
               "tags" => array("natural"=>"water",
                               "water"=>"lake",
                               "name"=>"Lake Whatever"),
               "members" => array(),
               "id" => 698,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78689843,78689623,78689334,78689234,78689481),
               "lat" => array(479999018,479998757,479998719,479998982,479999105),
               "holes_lon" => array(),
               "holes_lat" => array(),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Way",
               "key" => "highway",
               "value" => "footway",
               "tags" => array("highway"=>"footway"),
               "members" => array(array("id"=>64,"role"=>"","nr"=>1)),
               "id" => 584,
               "timestamp" => 1705738026,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78688273,78689066,78688829,78689549),
               "lat" => array(479998332,479998511,479999049,479999615),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Way",
               "key" => "highway",
               "value" => "footway",
               "tags" => array("highway"=>"footway"),
               "members" => array(array("id"=>64,"role"=>"","nr"=>2)),
               "id" => 586,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78689549,78689093),
               "lat" => array(479999615,479999995),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Way",
               "key" => "highway",
               "value" => "footway",
               "tags" => array("highway"=>"footway"),
               "members" => array(),
               "id" => 600,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78689549,78690369),
               "lat" => array(479999615,479999337),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Way",
               "key" => "highway",
               "value" => "footway",
               "tags" => array("highway"=>"footway"),
               "members" => array(array("id"=>64,"role"=>"","nr"=>0)),
               "id" => 696,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78688326,78688094,78687542,78687716,78688273),
               "lat" => array(479999849,479999629,479999320,479998800,479998332),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Area",
               "key" => "landuse",
               "value" => "meadow",
               "tags" => array("landuse"=>"meadow",
                               "type"=>"multipolygon"),
               "members" => array(),
               "id" => 59,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => array(78688982,78690999,78688593,78687201,78687337,78687968),
               "lat" => array(480000241,479999235,479997914,479998817,479999872,480000206),
               "holes_lon" => array(array(78689481,78689234,78689334,78689623,78689843)),
               "holes_lat" => array(array(479999105,479998982,479998719,479998757,479999018)),
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Collection",
               "key" => "route",
               "value" => "",
               "tags" => array("route"=>"example",
                               "type"=>"route"),
               "members" => array(),
               "id" => 64,
               "timestamp" => 1751196153,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "defs" => array(),
               ];

        yield null;
        yield null;
    }

    //////////////////////////////////////////////////////////////////

    public function testEmpty()
    {
        $r = new OmaReader('tests/testdata/empty.oma');

        self::assertEquals(new BoundingBox(0,0,0,0),$r->getBoundingBox());
        self::assertFalse($r->isZipped());
        self::assertFalse($r->containsID());
        self::assertTrue($r->containsVersion());
        self::assertFalse($r->containsTimestamp());
        self::assertTrue($r->containsChangeset());
        self::assertTrue($r->containsUser());
        self::assertTrue($r->elementsOnce());

        self::assertFalse($r->containsBlocks(ord('W'),'highway'));
        self::assertFalse($r->containsSlices(ord('W'),'highway','service'));

        $h = $r->next();
        self::assertSame($h,null);

        $r->reset();
        self::assertSame(0,$r->count());

        $r->close();
    }

    public function testEmpty2()
    {
        $r = new OmaReader('tests/testdata/empty2.oma');

        self::assertEquals(new BoundingBox(-1,-1,1,1),$r->getBoundingBox());
        self::assertTrue($r->isZipped());
        self::assertFalse($r->containsID());
        self::assertFalse($r->containsVersion());
        self::assertFalse($r->containsTimestamp());
        self::assertFalse($r->containsChangeset());
        self::assertFalse($r->containsUser());
        self::assertFalse($r->elementsOnce());

        $r->close();
    }

    public function testDefect()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("oma-file expected");
        $r = new OmaReader('tests/testdata/defect.oma');
    }

    public function testWithoutID()
    {
        $r = new OmaReader('tests/testdata/withoutid.oma');
        self::assertEquals(new BoundingBox(-7201,-7914,999,241),$r->getBoundingBox());
        self::assertFalse($r->isZipped());
        self::assertFalse($r->containsID());
        self::assertFalse($r->containsVersion());
        self::assertFalse($r->containsTimestamp());
        self::assertFalse($r->containsChangeset());
        self::assertFalse($r->containsUser());
        self::assertFalse($r->elementsOnce());

        foreach ($this->withoutIDElements() as $data)
        {
            $el = $r->next();

            if ($data==null)
            {
                self::assertSame($el,null);
                continue;
            }

            self::assertSame($data['type'],      get_class($el));
            self::assertSame($data['key'],       $el->key);
            self::assertSame($data['value'],     $el->value);
            self::assertSame($data['id'],        $el->id);
            self::assertSame($data['timestamp'], $el->timestamp);
            self::assertSame($data['version'],   $el->version);
            self::assertSame($data['changeset'], $el->changeset);
            self::assertSame($data['uid'],       $el->uid);
            self::assertSame($data['user'],      $el->user);
            self::assertEqualsCanonicalizing($data['tags'],    $el->tags);
            self::assertEqualsCanonicalizing($data['members'], $el->members);

            switch ($data['type'])
            {
             case 'Kumakyoo\OmaLib\Elements\Node':
                self::assertSame($data['lon'], $el->lon);
                self::assertSame($data['lat'], $el->lat);
                break;
             case 'Kumakyoo\OmaLib\Elements\Collection':
                self::assertCount(count($data['defs']),$el->defs);
                // later, when implemented: further tests
                break;
            }
        }

        $r->reset();
        self::assertSame(3,$r->count());

        $r->reset();
        $c = 0;
        foreach ($r->elements() as $v)
          $c++;
        self::assertSame(3,$c);

        $r->reset();
        $r->next();
        $r->reset();
        self::assertSame(3,$r->count());

        $r->close();
    }

    public function withoutIDElements()
    {
        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "tree",
               "tags" => array("natural"=>"tree"),
               "members" => array(array("id"=>64,"role"=>"","nr"=>0)),
               "id" => false,
               "timestamp" => false,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => 7052,
               "lat" => 183,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Node",
               "key" => "natural",
               "value" => "rock",
               "tags" => array("natural"=>"rock"),
               "members" => array(),
               "id" => false,
               "timestamp" => false,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "lon" => -7052,
               "lat" => -183,
               ];

        yield [
               "type" => "Kumakyoo\OmaLib\Elements\Collection",
               "key" => "route",
               "value" => "",
               "tags" => array("route"=>"example",
                               "type"=>"route"),
               "members" => array(),
               "id" => 64,
               "timestamp" => false,
               "version" => false,
               "changeset" => false,
               "uid" => false,
               "user" => false,
               "defs" => array(),
               ];
    }

    public function testTypeTableUnzipped()
    {
        $r = new OmaReader('tests/testdata/typetableunzipped.oma');

        self::assertEquals(new BoundingBox(0x7fffffff,0x7fffffff,0x7fffffff,0x7fffffff),$r->getBoundingBox());
        self::assertFalse($r->isZipped());
        self::assertTrue($r->containsID());
        self::assertFalse($r->containsVersion());
        self::assertFalse($r->containsTimestamp());
        self::assertFalse($r->containsChangeset());
        self::assertFalse($r->containsUser());
        self::assertFalse($r->elementsOnce());

        self::assertEqualsCanonicalizing($r->keySet(ord('N')),['natural']);

        $el = $r->next();

        self::assertSame('Kumakyoo\OmaLib\Elements\Node', get_class($el));
        self::assertSame(6510, $el->id);

        $r->close();
    }

    public function testNoneCompression()
    {
        $r = new OmaReader('tests/testdata/none.oma');
        self::assertFalse($r->isZipped());
    }

    public function testCompressionType()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("unknown compression method: DEFALTE");
        $r = new OmaReader('tests/testdata/defalte.oma');
    }

    public function testClosedStream()
    {
        $r = new OmaReader('tests/testdata/typetableunzipped.oma');
        $r->close();

        self::expectException(\Exception::class);
        self::expectExceptionMessage("OmaInputStream closed");
        $el = $r->next();
    }

    public function testSkipUnknownHeader()
    {
        $r = new OmaReader('tests/testdata/unknownheader.oma');
        self::assertEqualsCanonicalizing($r->keySet(ord('N')),['na']);
        $r->close();
    }

    public function testFilter()
    {
        $r = new OmaReader('tests/testdata/example.oma');
        self::assertSame('Kumakyoo\OmaLib\Filter\Filter', get_class($r->getFilter()));
        $r->setFilter(null);
        self::assertSame(null,$r->getFilter());
    }

    #[DataProvider('filterTestDataProvider')]
    public function testFilters($filter, $count_exp, $sum_exp)
    {
        $r = new OmaReader('tests/testdata/filtertest.oma');

        $r->setFilter($filter);
        self::assertSame($count_exp,$r->count());
        $sum = 0;
        $r->reset();
        foreach ($r->elements() as $el)
          $sum += $el->id;
        self::assertSame($sum_exp,$sum);
    }

    public static function filterTestDataProvider()
    {
        $r = new OmaReader('tests/testdata/filtertest.oma');
        $r->setFilter(new TypeFilter('C'));
        $c = $r->next();

        return [
                "ID"         => [ "filter"=>new IDFilter(1), "count_exp"=>1, "sum_exp"=>1 ],
                "Version"    => [ "filter"=>new VersionFilter(2), "count_exp"=>6, "sum_exp"=>994 ],
                "Timestamp"  => [ "filter"=>new TimestampFilter(1751196000,1751197000), "count_exp"=>4, "sum_exp"=>1346 ],
                "Changeset"  => [ "filter"=>new ChangesetFilter(12345,12543), "count_exp"=>5, "sum_exp"=>1101 ],
                "uid"        => [ "filter"=>new UserFilter(42), "count_exp"=>6, "sum_exp"=>3462 ],
                "User"       => [ "filter"=>new UserFilter('kumakyoo'), "count_exp"=>5, "sum_exp"=>213 ],

                "Type1"      => [ "filter"=>new TypeFilter('NA'), "count_exp"=>6, "sum_exp"=>159 ],
                "Type2"      => [ "filter"=>new TypeFilter('W'), "count_exp"=>6, "sum_exp"=>5984 ],
                "Type3"      => [ "filter"=>new TypeFilter('C'), "count_exp"=>1, "sum_exp"=>2048 ],
                "Key"        => [ "filter"=>new KeyFilter('name'), "count_exp"=>2, "sum_exp"=>2049 ],
                "Tag"        => [ "filter"=>new TagFilter('lit','no'), "count_exp"=>3, "sum_exp"=>2082 ],
                "Block1"     => [ "filter"=>new BlockFilter('highway'), "count_exp"=>5, "sum_exp"=>1888 ],
                "Block2"     => [ "filter"=>new BlockFilter('waterway'), "count_exp"=>1, "sum_exp"=>4096 ],
                "BlockSlice" => [ "filter"=>new BlockSliceFilter('highway','footway'), "count_exp"=>1, "sum_exp"=>32 ],

                "Member1"    => [ "filter"=>new MemberFilter($c,null), "count_exp"=>2, "sum_exp"=>768 ],
                "Member2"    => [ "filter"=>new MemberFilter($c,'end'), "count_exp"=>1, "sum_exp"=>512 ],

                "LC1"        => [ "filter"=>new LifecycleFilter(), "count_exp"=>11, "sum_exp"=>7151 ],
                "LC2"        => [ "filter"=>new LifecycleFilter("planned"), "count_exp"=>2, "sum_exp"=>1040 ],

                "And" => [
                          "filter"=>new AndFilter(new TimestampFilter(1751196000,1751197000),new ChangesetFilter(12345,12543)),
                          "count_exp"=>2,
                          "sum_exp"=>1088
                          ],
                "Or" => [
                          "filter"=>new OrFilter(new TimestampFilter(1751196000,1751197000),new ChangesetFilter(12345,12543)),
                          "count_exp"=>7,
                          "sum_exp"=>1359
                          ],
                "Not" => [
                          "filter"=>new NotFilter(new TypeFilter('NA')),
                          "count_exp"=>7,
                          "sum_exp"=>8032
                          ],
                ];
    }
}
