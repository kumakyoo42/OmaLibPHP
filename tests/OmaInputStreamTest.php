<?php

namespace Kumakyoo\OmaLib\Tests;

use PHPUnit\Framework\TestCase;
use Kumakyoo\OmaLib\OmaInputStream;

class OmaInputStreamTest extends TestCase
{
    public function testExampleFile()
    {
        $ois = new OmaInputStream(fopen('tests/testdata/example.oma','r'));

        self::assertSame(0x4f,$ois->readByte());
        self::assertSame(0x4d41,$ois->readShort());
        self::assertSame(0x010504b0,$ois->readInt());
        $ois->readShort();
        self::assertSame(0x1c9c2fda04b0bab7,$ois->readLong());
        self::assertSame(0x1c,$ois->readSmallInt());
        $ois->seek(0x22);
        self::assertSame("DEFLATE",$ois->readString());
        $ois->seek(0x239);
        self::assertSame(0xddff,$ois->readSmallInt());
        $ois->seek(0x492);
        self::assertSame(0x7fffffff,$ois->readSmallInt());
        self::assertSame("\x7f\xff\xff\xff\x7f",$ois->readBytes(5));

        $ois->close();
    }

    public function testString()
    {
        $ois = new OmaInputStream("abcdefghijklmno\x01\xffpq\xff\xff\xffrstu\x04vwxyzzz");

        self::assertSame(0x61,$ois->readByte());
        self::assertSame(0x6263,$ois->readShort());
        self::assertSame(0x64656667,$ois->readInt());
        self::assertSame(0x68696a6b6c6d6e6f,$ois->readLong());
        self::assertSame(1,$ois->readSmallInt());
        self::assertSame(0x7071,$ois->readSmallInt());
        self::assertSame(0x72737475,$ois->readSmallInt());
        self::assertSame('vwxy',$ois->readString());
        self::assertSame('zzz',$ois->readBytes(3));

        $ois->close();
    }

    public function testDelta()
    {
        $ois = new OmaInputStream("\x80\x00\x00\x01\x00\x01\x00\x01\xff\xff\x80\x00\x00\x01\x00\x01\x00\x01\xff\xff");
        $ois->resetDelta();
        self::assertSame(0x10001,$ois->readDeltaX());
        self::assertSame(0x10002,$ois->readDeltaX());
        self::assertSame(0x10001,$ois->readDeltaX());
        self::assertSame(0x10001,$ois->readDeltaY());
        self::assertSame(0x10002,$ois->readDeltaY());
        self::assertSame(0x10001,$ois->readDeltaY());
        $ois->close();

        $ois = new OmaInputStream("\x00\x01\xff\xff");
        $ois->resetDelta();
        self::assertSame(1,$ois->readDeltaX());
        self::assertSame(-1,$ois->readDeltaY());
        $ois->close();
    }

    public function testWrongArgument()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("cannot initialize OmaInputStream");
        $ois = new OmaInputStream(7);
    }

    public function testEndOfString()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("end of OmaInputStream reached");
        $ois = new OmaInputStream("\x00\x00\x00");
        $ois->readInt();
    }

    public function testEndOfFile()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage("end of OmaInputStream reached");
        $ois = new OmaInputStream(fopen('tests/testdata/short.oma','r'));
        $ois->readInt();
    }

    public function testCornerCases()
    {
        $ois = new OmaInputStream("\x80\x80\x00\x80\x00\x00\x00\x80\x00\x00\x00\x00\x00\x00\x00");
        self::assertSame(-128,$ois->readByte());
        self::assertSame(-32768,$ois->readShort());
        self::assertSame(-2147483648,$ois->readInt());
        self::assertSame(-9223372036854775807-1,$ois->readLong()); // -1 to avoid floating point
    }
}
