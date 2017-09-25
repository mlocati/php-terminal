<?php

namespace MLocati\Terminal\Test;

use Exception;
use MLocati\Terminal\CommandLine\Arguments\Joiner;
use MLocati\Terminal\CommandLine\WindowsCodepager;

class ArgumentsDumper
{
    private $assetsDirectory;

    private $programPath;

    private static $windowsCodepager;

    public function getArguments(array $arguments)
    {
        $executable = $this->getProgramPath();
        $joiner = new Joiner();
        $commandLine = $joiner->joinCommandLine(array_merge([$executable], $arguments));

        return $this->runCommand($commandLine);
    }

    public function runCommand($commandLine)
    {
        $rc = -1;
        $output = [];
        @exec($commandLine, $output, $rc);
        if ($rc !== 0) {
            throw new Exception(basename($executable) . " failed with return code {$rc}: " . trim(implode("\n", $output)));
        }
        if ('\\' === DIRECTORY_SEPARATOR) {
            foreach (array_keys($output) as $i) {
                $output[$i] = self::getWindowsCodepager()->decode($output[$i]);
            }
        }
        $numArguments = (int) array_shift($output);
        $dump = trim(implode("\n", $output));
        $tempDump = $dump . "\n#{$numArguments}>>>";
        $result = [];
        for ($index = 0; $index < $numArguments; ++$index) {
            $start = "#{$index}>>>";
            if (strpos($tempDump, $start) !== 0) {
                throw new Exception("Failed to parse {$dump}");
            }
            $tempDump = substr($tempDump, strlen($start));
            $nextStart = strpos($tempDump, "<<<\n#" . ($index + 1) . '>>>');
            if ($nextStart === false) {
                throw new Exception("Failed to parse {$dump}");
            }
            $result[] = $nextStart === 0 ? '' : substr($tempDump, 0, $nextStart);
            $tempDump = substr($tempDump, $nextStart + strlen("<<<\n"));
        }

        return $result;
    }

    public function getProgramPath()
    {
        if ($this->programPath === null) {
            try {
                $this->programPath = '\\' === DIRECTORY_SEPARATOR ? $this->getProgramPath_Windows() : $this->getProgramPath_Posix();
            } catch (Exception $x) {
                $this->programPath = $x;
            }
        }
        if ($this->programPath instanceof Exception) {
            throw $this->programPath;
        }

        return $this->programPath;
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

    private function getProgramPath_Posix()
    {
        $architecture = @php_uname('m');
        if (!$architecture) {
            throw new Exception('Failed to determine the POSIX architecture.');
        }
        $path = $this->getAssetsDirectory() . '/bin/ArgumentsDumper.' . $architecture;
        if (!is_file($path)) {
            $binDir = $this->getAssetsDirectory() . '/bin';
            if (!is_dir($binDir)) {
                @mkdir($binDir);
            }
            $output = [];
            $rc = -1;
            @exec('gcc -pass-exit-codes -s ' . escapeshellarg($this->getAssetsDirectory() . '/ArgumentsDumper.c') . ' -o ' . escapeshellarg($path) . ' 2>&1', $output, $rc);
            if ($rc !== 0) {
                throw new Exception("GCC failed with return code {$rc}: " . trim(implode("\n", $output)));
            }
        }

        return $path;
    }

    private function getProgramPath_Windows()
    {
        $path = $this->getAssetsDirectory() . '\\bin\\ArgumentsDumper.exe';
        if (!is_file($path)) {
            $binDir = $this->getAssetsDirectory() . '\\bin';
            if (!is_dir($binDir)) {
                @mkdir($binDir);
            }
            $output = [];
            $rc = -1;
            @exec('cd /d ' . escapeshellarg($this->getAssetsDirectory()) . ' && cl.exe /nologo /O1 /Os /Oy /WX /Gy /Febin\\ArgumentsDumper.exe ArgumentsDumper.c 2>&1', $output, $rc);
            if ($rc !== 0) {
                throw new Exception("cl.exe failed with return code {$rc}: " . trim(implode("\n", $output)));
            }
            @unlink($this->getAssetsDirectory() . '\\ArgumentsDumper.obj');
        }

        return $path;
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
