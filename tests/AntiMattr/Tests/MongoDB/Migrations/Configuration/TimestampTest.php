<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use PHPUnit\Framework\TestCase;
use AntiMattr\MongoDB\Migrations\Configuration\Timestamp;

class TimestampTest extends TestCase
{
    /**
     * @dataProvider provideTimestamps
     */
    public function testTimestamp($ts, $expected)
    {
        self::assertEquals($expected, (new Timestamp($ts))->getTimestamp());
    }

    public function provideTimestamps(): array
    {
        $rightNow = new \DateTimeImmutable();
        $secondsSince1970 = (int) $rightNow->format('U');
        $millisecondsSince1970 = $secondsSince1970 * 1000;

        $timestamps = [
            [$rightNow, $secondsSince1970],
        ];

        if (class_exists('\MongoDB\BSON\Timestamp')) {
            $timestamps[] = [
                new \MongoDB\BSON\Timestamp(0, $secondsSince1970),
                $secondsSince1970,
            ];
        }

        if (class_exists('\MongoDB\BSON\UTCDateTime')) {
            $timestamps[] = [
                new \MongoDB\BSON\UTCDateTime($millisecondsSince1970),
                $secondsSince1970,
            ];
        }

        if (class_exists('\MongoDate')) {
            $timestamps[] = [
                new \MongoDate($secondsSince1970),
                $secondsSince1970,
            ];
        }

        if (class_exists('\MongoTimestamp')) {
            $timestamps[] = [
                new \MongoTimestamp($secondsSince1970),
                $secondsSince1970,
            ];
        }

        return $timestamps;
    }

    /**
     * @expectedException \DomainException
     */
    public function testWillThrowAnExceptionForUnknownClass()
    {
        (new Timestamp(new \stdClass()))->getTimestamp();
    }

    /**
     * @expectedException \DomainException
     */
    public function testWillThrowAnExceptionForNull()
    {
        (new Timestamp(null))->getTimestamp();
    }
}
