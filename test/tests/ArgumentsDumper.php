<?php

namespace MLocati\Terminal\Test;

use Exception;
use MLocati\Terminal\CommandLine\Arguments\Joiner;
use MLocati\Terminal\CommandLine\Arguments\Splitter;
use MLocati\Terminal\CommandLine\Detector;
use MLocati\Terminal\CommandLine\WindowsCodepager;

class ArgumentsDumper
{
    private $assetsDirectory;

    private $phpExePath;

    private static $windowsCodepager;

    public function getArguments(array $arguments)
    {
        $joiner = new Joiner();
        $joiner->setWindowsCodepager(self::getWindowsCodepager());
        $cmd = $joiner->joinCommandLineArguments($arguments);

        return $this->runCommand($cmd);
    }

    public function runCommand($commandLine)
    {
        $joiner = new Joiner();
        $joiner->setWindowsCodepager(self::getWindowsCodepager());
        $cmd = $joiner->joinCommandLine([$this->getPHPExePath(), $this->getAssetsDirectory() . DIRECTORY_SEPARATOR . 'ArgumentsDumper.php']) . ' ' . $commandLine;
        $rc = -1;
        $output = [];
        @exec($cmd, $output, $rc);
        if ($rc !== 0) {
            throw new Exception("ArgumentsDumper failed with return code {$rc}: " . trim(implode("\n", $output)));
        }
        $decoded = @json_decode(trim(implode("\n", $output)));
        if (!is_array($decoded)) {
            throw new Exception('Failed to parse ' . implode("\n", $output));
        }

        return $decoded;
    }

    public function getWindowsCodepage()
    {
        return $this->getWindowsCodepager()->getCodepage();
    }

    private function getPHPExePath()
    {
        if ($this->phpExePath === null) {
            try {
                $detector = new Detector();
                $commandLine = $detector->getCommandLine();
                $splitter = new Splitter();
                $parts = $splitter->splitCommandLine($commandLine);
                $this->phpExePath = $parts[0];
            } catch (Exception $x) {
                $this->phpExePath = $x;
            }
        }
        if ($this->phpExePath instanceof Exception) {
            throw $this->phpExePath;
        }

        return $this->phpExePath;
    }

    private function getAssetsDirectory()
    {
        if ($this->assetsDirectory === null) {
            $this->assetsDirectory = @realpath(dirname(__DIR__) . '/assets');
        }
        if ($this->assetsDirectory === false) {
            throw new Exception('Failed to locate the assets directory.');
        }

        return $this->assetsDirectory;
    }

    /**
     * @return WindowsCodepager
     */
    private static function getWindowsCodepager()
    {
        if (self::$windowsCodepager === null) {
            self::$windowsCodepager = new WindowsCodepager();
        }

        return self::$windowsCodepager;
    }
}
