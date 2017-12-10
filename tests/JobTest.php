<?php

namespace PETest\Component\Cron;

use PE\Component\Cron\Exception\InvalidArgumentException;
use PE\Component\Cron\Exception\RuntimeException;
use PE\Component\Cron\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Job
     */
    protected $job;

    protected function setUp()
    {
        $this->job = new Job();
    }

    public function testGettersAndSetters()
    {
        // Strings
        static::assertEquals('foo', $this->job->setCommand('foo')->getCommand());
        static::assertEquals('foo', $this->job->setComment('foo')->getComment());
        static::assertEquals('foo', $this->job->setDayOfMonth('foo')->getDayOfMonth());
        static::assertEquals('foo', $this->job->setDayOfWeek('foo')->getDayOfWeek());
        static::assertEquals('foo', $this->job->setErrorFile('foo')->getErrorFile());
        static::assertEquals('foo', $this->job->setHour('foo')->getHour());
        static::assertEquals('foo', $this->job->setLogFile('foo')->getLogFile());
        static::assertEquals('foo', $this->job->setMinute('foo')->getMinute());
        static::assertEquals('foo', $this->job->setMonth('foo')->getMonth());

        //Enum
        static::assertEquals(Job::STATUS_ERROR, $this->job->setStatus(Job::STATUS_ERROR)->getStatus());

        // Boolean
        static::assertFalse($this->job->isSuspended());
        static::assertTrue($this->job->setSuspended(true)->isSuspended());

        $date = new \DateTime();
        static::assertNull($this->job->getLastRunTime());
        static::assertEquals($date, $this->job->setLastRunTime($date)->getLastRunTime());
    }

    public function testSetInvalidStatsThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->job->setStatus('foo');

    }

    public function testToStringWithoutSetCommandThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->job->toString();
    }

    public function testToString()
    {
        $this->job->setCommand('foo');

        static::assertEquals('* * * * * foo', $this->job->toString());
        static::assertEquals('*/5 * * * * foo', $this->job->setMinute('*/5')->toString());
        static::assertEquals('*/5 0 * * * foo', $this->job->setHour('0')->toString());
        static::assertEquals('*/5 0 1 * * foo', $this->job->setDayOfMonth('1')->toString());
        static::assertEquals('*/5 0 1 2 * foo', $this->job->setMonth('2')->toString());
        static::assertEquals('*/5 0 1 2 3 foo', $this->job->setDayOfWeek('3')->toString());
        static::assertEquals('*/5 0 1 2 3 bar', (string) $this->job->setCommand('bar')->toString());

        static::assertEquals(
            '#suspended: */5 0 1 2 3 bar',
            $this->job->setSuspended()->toString()
        );

        static::assertEquals(
            '#suspended: */5 0 1 2 3 bar > log.txt',
            $this->job->setLogFile('log.txt')->toString()
        );

        static::assertEquals(
            '#suspended: */5 0 1 2 3 bar > log.txt 2> error.txt',
            $this->job->setErrorFile('error.txt')->toString()
        );

        static::assertEquals(
            '#suspended: */5 0 1 2 3 bar > log.txt 2> error.txt #comment',
            $this->job->setComment('comment')->toString()
        );
    }

    public function testFromInvalidStringThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Job::fromString(1);
    }

    public function testFromInvalidStringFromatThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Job::fromString('#suspended: * *');
    }

    public function testFromString()
    {
        $job = Job::fromString(
            '#suspended: */5 0 1 2 3 bar'
            . ' > ' . __DIR__ . '/TestAsset/log.txt'
            . ' 2> ' . __DIR__ . '/TestAsset/error.txt #comment'
        );

        static::assertTrue($job->isSuspended());
        static::assertEquals('*/5', $job->getMinute());
        static::assertEquals('0', $job->getHour());
        static::assertEquals('1', $job->getDayOfMonth());
        static::assertEquals('2', $job->getMonth());
        static::assertEquals('3', $job->getDayOfWeek());
        static::assertEquals('bar', $job->getCommand());
        static::assertEquals(__DIR__ . '/TestAsset/log.txt', $job->getLogFile());
        static::assertEquals(__DIR__ . '/TestAsset/error.txt', $job->getErrorFile());
        static::assertEquals('comment', $job->getComment());
        static::assertEquals(Job::STATUS_ERROR, $job->getStatus());

        static::assertInstanceOf(\DateTime::class, $job->getLastRunTime());
    }
}
