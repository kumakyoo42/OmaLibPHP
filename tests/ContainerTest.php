<?php

namespace Kumakyoo\OmaLib\Tests;

use PHPUnit\Framework\TestCase;
use Kumakyoo\OmaLib\OmaReader;
use Kumakyoo\OmaLib\OmaInputStream;
use Kumakyoo\OmaLib\Filter\BlockFilter;
use Kumakyoo\OmaLib\Elements\Area;
use Kumakyoo\OmaLib\Container\BoundingBox;
use Kumakyoo\OmaLib\Container\TightBoundingBox;
use Kumakyoo\OmaLib\Container\Polygon;
use Kumakyoo\OmaLib\Container\TightPolygon;

class ContainerTest extends TestCase
{
    public function testBoundingBox()
    {
        $b = new BoundingBox(0,1,2,3);
        self::assertTrue($b->containsPoint(0,1));
        self::assertTrue($b->containsPoint(2,1));
        self::assertTrue($b->containsPoint(0,3));
        self::assertTrue($b->containsPoint(2,3));
        self::assertTrue($b->containsPoint(1,2));
        self::assertFalse($b->containsPoint(0,0));
        self::assertFalse($b->containsPoint(3,3));
        self::assertFalse($b->containsPoint(4,4));

        $b = new BoundingBox([0,1,2,3]);
        self::assertTrue($b->containsPoint(0,1));
        self::assertTrue($b->containsPoint(2,1));
        self::assertTrue($b->containsPoint(0,3));
        self::assertTrue($b->containsPoint(2,3));
        self::assertTrue($b->containsPoint(1,2));
        self::assertFalse($b->containsPoint(0,0));
        self::assertFalse($b->containsPoint(3,3));
        self::assertFalse($b->containsPoint(4,4));

        $b = new BoundingBox($b);
        self::assertTrue($b->containsPoint(0,1));
        self::assertTrue($b->containsPoint(2,1));
        self::assertTrue($b->containsPoint(0,3));
        self::assertTrue($b->containsPoint(2,3));
        self::assertTrue($b->containsPoint(1,2));
        self::assertFalse($b->containsPoint(0,0));
        self::assertFalse($b->containsPoint(3,3));
        self::assertFalse($b->containsPoint(4,4));

        $ois = new OmaInputStream("\x00\x00\x00\x00\x00\x00\x00\x01\x00\x00\x00\x02\x00\x00\x00\x03");
        $b = new BoundingBox($ois);
        self::assertTrue($b->containsPoint(0,1));
        self::assertTrue($b->containsPoint(2,1));
        self::assertTrue($b->containsPoint(0,3));
        self::assertTrue($b->containsPoint(2,3));
        self::assertTrue($b->containsPoint(1,2));
        self::assertFalse($b->containsPoint(0,0));
        self::assertFalse($b->containsPoint(3,3));
        self::assertFalse($b->containsPoint(4,4));
    }

    public function testBoundingBoxException1()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for BoundingBox");
        $b = new BoundingBox(1);
    }

    public function testBoundingBoxException2()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for BoundingBox");
        $b = new BoundingBox(1,2);
    }

    public function testNoBoundingBox()
    {
        $b = new BoundingBox(0x7fffffff,0x7fffffff,0x7fffffff,0x7fffffff);
        self::assertTrue($b->containsPoint(0,1));
        self::assertTrue($b->containsPoint(2,1));
        self::assertTrue($b->containsPoint(0,3));
        self::assertTrue($b->containsPoint(2,3));
        self::assertTrue($b->containsPoint(1,2));
        self::assertTrue($b->containsPoint(0,0));
        self::assertTrue($b->containsPoint(3,3));
        self::assertTrue($b->containsPoint(4,4));
    }

    public function testBoundingBoxContainsLine()
    {
        $b = new BoundingBox(0,10,20,30);
        self::assertTrue($b->containsLine([2,8],[12,18]));
        self::assertTrue($b->containsLine([-3,8],[-3,18]));
        self::assertTrue($b->containsLine([2,50],[12,50]));
        self::assertFalse($b->containsLine([-3,50],[-3,50]));
    }

    public function testBoundingBoxContainsBoundingBox()
    {
        $b = new BoundingBox(0,10,20,30);
        self::assertTrue($b->containsBoundingBox($b));
        self::assertTrue($b->containsBoundingBox(new BoundingBox(2,12,8,18)));
        self::assertFalse($b->containsBoundingBox(new BoundingBox(-3,12,8,18)));
        self::assertFalse($b->containsBoundingBox(new BoundingBox(2,-3,8,18)));
        self::assertFalse($b->containsBoundingBox(new BoundingBox(-3,-3,50,50)));
    }

    public function testBoundingBoxIntersects()
    {
        $b = new BoundingBox(0,10,20,30);
        self::assertTrue($b->intersects($b));
        self::assertTrue($b->intersects(new BoundingBox(2,12,8,18)));
        self::assertTrue($b->intersects(new BoundingBox(-3,12,8,18)));
        self::assertTrue($b->intersects(new BoundingBox(2,-3,8,18)));
        self::assertTrue($b->intersects(new BoundingBox(-3,-3,50,50)));
        self::assertFalse($b->intersects(new BoundingBox(-3,-3,-1,-1)));
        self::assertTrue($b->intersects(new BoundingBox(0x7fffffff,0x7fffffff,0x7fffffff,0x7fffffff)));

        $b = new BoundingBox(0x7fffffff,0x7fffffff,0x7fffffff,0x7fffffff);
        self::assertTrue($b->intersects(new BoundingBox(-3,-3,-1,-1)));
    }

    public function testTightBoundingBoxContainsLine()
    {
        $b = new TightBoundingBox(0,10,20,30);
        self::assertTrue($b->containsLine([2,8],[12,18]));
        self::assertFalse($b->containsLine([-3,8],[-3,18]));
        self::assertFalse($b->containsLine([2,50],[12,50]));
        self::assertFalse($b->containsLine([-3,50],[-3,50]));
    }

    //////////////////////////////////////////////////////////////////

    public function testFilePolygon1()
    {
        $p = new Polygon("tests/testdata/p1.poly");
        self::assertTrue($p->containsPoint(120000000,170000000));
        self::assertTrue($p->containsPoint(110000000,180000000));
        self::assertTrue($p->containsPoint(130000000,180000000));
        self::assertFalse($p->containsPoint(100000000,180000000));
        self::assertFalse($p->containsPoint(150000000,180000000));
        self::assertFalse($p->containsPoint(100000000,150000000));
        self::assertFalse($p->containsPoint(150000000,150000000));
        self::assertFalse($p->containsPoint(120000000,200000000));
        self::assertFalse($p->containsPoint(120000000,300000000));
    }

    public function testFilePolygon2()
    {
        $p = new Polygon("tests/testdata/p2.poly");
        self::assertFalse($p->containsPoint(100000000,100000000));
        self::assertFalse($p->containsPoint(100000003,100000000));
        self::assertFalse($p->containsPoint(100000006,100000000));
        self::assertFalse($p->containsPoint(100000003,100000001));
        self::assertFalse($p->containsPoint(100000000,100000002));
        self::assertFalse($p->containsPoint(100000006,100000002));
        self::assertFalse($p->containsPoint(100000000,100000003));
        self::assertFalse($p->containsPoint(100000006,100000003));
        self::assertFalse($p->containsPoint(100000000,100000004));
        self::assertFalse($p->containsPoint(100000006,100000004));
        self::assertFalse($p->containsPoint(100000000,100000005));
        self::assertFalse($p->containsPoint(100000003,100000005));
        self::assertFalse($p->containsPoint(100000006,100000005));
        self::assertFalse($p->containsPoint(100000000,100000007));
        self::assertFalse($p->containsPoint(100000006,100000007));
    }

    public function testFileWithSizePolygon1()
    {
        $p = new Polygon("tests/testdata/p1.poly",10000000);
        self::assertTrue($p->containsPoint(120000000,170000000));
        self::assertTrue($p->containsPoint(110000000,180000000));
        self::assertTrue($p->containsPoint(130000000,180000000));
        self::assertFalse($p->containsPoint(100000000,180000000));
        self::assertFalse($p->containsPoint(150000000,180000000));
        self::assertFalse($p->containsPoint(100000000,150000000));
        self::assertFalse($p->containsPoint(150000000,150000000));
        self::assertFalse($p->containsPoint(120000000,200000000));
        self::assertFalse($p->containsPoint(120000000,300000000));
    }

    public function testQueryPolygon()
    {
        $r = new OmaReader('tests/testdata/example.oma');
        $p = new Polygon($r,new BlockFilter('landuse'));
        self::assertTrue($p->containsPoint(78688900,480000000));
        self::assertFalse($p->containsPoint(78690000,480000000));
        self::assertFalse($p->containsPoint(78689400,479998800));
        self::assertTrue($p->containsPoint(78690000,479998800));
    }

    public function testQueryPolygonWithSize()
    {
        $r = new OmaReader('tests/testdata/example.oma');
        $p = new Polygon($r,new BlockFilter('landuse'),10000000);
        self::assertTrue($p->containsPoint(78688900,480000000));
        self::assertFalse($p->containsPoint(78690000,480000000));
        self::assertFalse($p->containsPoint(78689400,479998800));
        self::assertTrue($p->containsPoint(78690000,479998800));
    }

    public function testPolygonFromAreas()
    {
        $ois = new OmaInputStream("\x04\x00\x00\x00\x00\x00\x10\x00\x10\x10\x00\x10\x00\xff\xe0\x00\x00\x00".
                                  "\x04\x80\x00\x00\x10\x00\x00\x80\x00\x00\x10\x00\x00\x00\x10\x00\x10\x10\x00\x10\x00\xff\xe0\x00\x00\x00");
        $a1 = new Area($ois,"a","b");
        $a2 = new Area($ois,"a","b");
        $p = new Polygon([$a1,$a2]);
        self::assertTrue($p->containsPoint(8,8));
        self::assertFalse($p->containsPoint(15,8));
        self::assertFalse($p->containsPoint(8,15));
        self::assertTrue($p->containsPoint(0x100000+8,0x100000+8));
        self::assertFalse($p->containsPoint(0x100000+15,0x100000+8));
        self::assertFalse($p->containsPoint(0x100000+8,0x100000+15));
    }

    public function testPolygonFromAreasWithSize()
    {
        $ois = new OmaInputStream("\x04\x00\x00\x00\x00\x00\x10\x00\x10\x10\x00\x10\x00\xff\xe0\x00\x00\x00".
                                  "\x04\x80\x00\x00\x10\x00\x00\x80\x00\x00\x10\x00\x00\x00\x10\x00\x10\x10\x00\x10\x00\xff\xe0\x00\x00\x00");
        $a1 = new Area($ois,"a","b");
        $a2 = new Area($ois,"a","b");
        $p = new Polygon([$a1,$a2],10000000);
        self::assertTrue($p->containsPoint(8,8));
        self::assertFalse($p->containsPoint(15,8));
        self::assertFalse($p->containsPoint(8,15));
        self::assertTrue($p->containsPoint(0x100000+8,0x100000+8));
        self::assertFalse($p->containsPoint(0x100000+15,0x100000+8));
        self::assertFalse($p->containsPoint(0x100000+8,0x100000+15));
    }

    public function testPolygonFromPolygon()
    {
        $p1 = new Polygon("tests/testdata/p1.poly",10000000);
        $p2 = new Polygon($p1);
        self::assertTrue($p2->containsPoint(120000000,170000000));
        self::assertTrue($p2->containsPoint(110000000,180000000));
        self::assertTrue($p2->containsPoint(130000000,180000000));
        self::assertFalse($p2->containsPoint(100000000,180000000));
        self::assertFalse($p2->containsPoint(150000000,180000000));
        self::assertFalse($p2->containsPoint(100000000,150000000));
        self::assertFalse($p2->containsPoint(150000000,150000000));
        self::assertFalse($p2->containsPoint(120000000,200000000));
        self::assertFalse($p2->containsPoint(120000000,300000000));
    }

    public function testBoundingBoxFromPolygon()
    {
        $r = new OmaReader('tests/testdata/example.oma');
        $p = new Polygon($r,new BlockFilter('landuse'));
        $b = $p->getBoundingBox();
        self::assertTrue($b->containsPoint(78687202,479997915));
        self::assertTrue($b->containsPoint(78690998,480000240));
        self::assertFalse($b->containsPoint(78687200,480000242));
        self::assertFalse($b->containsPoint(78691000,479997913));

        $p = new Polygon("tests/testdata/p4.poly");
        $b = $p->getBoundingBox();
        self::assertTrue($b->containsPoint(-15,-15));
    }

    public function testPolygonContainsLine()
    {
        $p = new Polygon("tests/testdata/p3.poly");
        self::assertFalse($p->containsLine([5,25],[5,25]));
        self::assertTrue($p->containsLine([5,15,25],[5,15,25]));
        self::assertTrue($p->containsLine([15,25],[15,25]));
        self::assertTrue($p->containsLine([5,15],[5,15]));
        self::assertTrue($p->containsLine([12,15,18],[12,15,18]));
    }

    public function testPolygonContainsBoundingBox()
    {
        $p = new Polygon("tests/testdata/p3.poly");
        self::assertFalse($p->containsBoundingBox(new BoundingBox(5,5,25,25)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(5,5,15,15)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(15,15,25,25)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(5,15,15,25)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(15,5,25,15)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(12,12,18,18)));
    }

    public function testTightPolygonContainsLine()
    {
        $p = new TightPolygon("tests/testdata/p3.poly");
        self::assertFalse($p->containsLine([5,25],[5,25]));
        self::assertFalse($p->containsLine([5,15,25],[5,15,25]));
        self::assertFalse($p->containsLine([15,25],[15,25]));
        self::assertFalse($p->containsLine([5,15],[5,15]));
        self::assertTrue($p->containsLine([12,15,18],[12,15,18]));
    }

    public function testTightPolygonContainsBoundingBox()
    {
        $p = new TightPolygon("tests/testdata/p3.poly");
        self::assertFalse($p->containsBoundingBox(new BoundingBox(5,5,25,25)));
        self::assertFalse($p->containsBoundingBox(new BoundingBox(5,5,15,15)));
        self::assertFalse($p->containsBoundingBox(new BoundingBox(15,15,25,25)));
        self::assertFalse($p->containsBoundingBox(new BoundingBox(5,15,15,25)));
        self::assertFalse($p->containsBoundingBox(new BoundingBox(15,5,25,15)));
        self::assertTrue($p->containsBoundingBox(new BoundingBox(12,12,18,18)));
    }

    public function testPolygonException1()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for Polygon");
        $b = new Polygon(1);
    }

    public function testPolygonException2()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for Polygon");
        $b = new Polygon(1,2);
    }

    public function testPolygonException3()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for Polygon");
        $b = new Polygon(1,2,3);
    }

    public function testPolygonException4()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("wrong parameters for Polygon");
        $b = new Polygon(1,2,3,4);
    }
}
