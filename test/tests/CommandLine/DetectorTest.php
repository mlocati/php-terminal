<?php

namespace MLocati\Terminal\Test\CommandLine;

use Exception;
use MLocati\Terminal\CommandLine\Arguments\Splitter;
use MLocati\Terminal\CommandLine\Detector;
use PHPUnit_Framework_TestCase;

class DetectorTest extends PHPUnit_Framework_TestCase
{
    public function testSettingProcessID()
    {
        $detector = new Detector();
        $detector->setProcessID('1');
        $processID = $detector->getProcessID();
        $this->assertSame(1, $processID);
        $detector->setProcessID(PHP_INT_MAX);
        $processID = $detector->getProcessID();
        $this->assertSame(PHP_INT_MAX, $processID);
    }

    public function testProcessIDDetected()
    {
        $detector = new Detector();
        $processID = $detector->getProcessID();
        $this->assertInternalType('integer', $processID);
    }

    public function testCommandDetected()
    {
        $detector = new Detector();
        $commandLine = $detector->getCommandLine();
        $this->assertInternalType('string', $commandLine);
        $splitter = new Splitter();
        try {
            $splitter->splitCommandLine($commandLine);
            $error = null;
        } catch (Exception $x) {
            $error = $x;
        }
        $this->assertNull($error);
    }
}
