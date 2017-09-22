<?php

namespace MLocati\Terminal\CommandLine;

/**
 * Class to determine the command line of a process.
 */
class Detector
{
    /**
     * The process ID for which you want the command line.
     *
     * @var int|null
     */
    protected $processID;

    /**
     * The WindowsCodepager instance to be used to decode Windows commands from the system codepage to UTF-8.
     *
     * @var WindowsCodepager|null
     */
    protected $windowsCodepager;

    /**
     * Set the process ID for which you want the command line.
     *
     * @param int|null $processID
     *
     * @return $this
     */
    public function setProcessID($processID)
    {
        if (is_int($processID) || (is_string($processID) && is_numeric($processID))) {
            $processID = (int) $processID;
        } else {
            $processID = null;
        }

        if ($this->processID !== $processID) {
            $this->processID = $processID;
        }

        return $this;
    }

    /**
     * Get the process ID for which you want the command line.
     * If it was not previously set, we'll try to detect  the current process ID.
     *
     * @return int|null
     */
    public function getProcessID()
    {
        if ($this->processID === null) {
            $this->setProcessID(@getmypid());
        }

        return $this->processID;
    }

    /**
     * Set the WindowsCodepager instance to be used to decode Windows commands from the system codepage to UTF-8.
     *
     * @param WindowsCodepager $windowsCodepager
     *
     * @return $this
     */
    public function setWindowsCodepager(WindowsCodepager $windowsCodepager)
    {
        $this->windowsCodepager = $windowsCodepager;

        return $this;
    }

    /**
     * Get the WindowsCodepager instance to be used to decode Windows commands from the system codepage to UTF-8.
     *
     * @return WindowsCodepager
     */
    public function getWindowsCodepager()
    {
        if ($this->windowsCodepager === null) {
            $this->windowsCodepager = new WindowsCodepager();
        }

        return $this->windowsCodepager;
    }

    /**
     * Detect the full command line of the specified process ID.
     *
     * @return string|null
     */
    public function getCommandLine()
    {
        $result = null;
        $processID = $this->getProcessID();
        if ($processID !== null) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $output = [];
                $rc = -1;
                @exec("wmic path win32_process where Processid={$processID} get Commandline 2>&1", $output, $rc);
                if ($rc === 0) {
                    $output = array_filter($output);
                    if (count($output) === 2) {
                        $result = $this->getWindowsCodepager()->decode(array_pop($output));
                    }
                }
            } else {
                $output = [];
                $rc = -1;
                @exec("ps -o args --pid {$processID} 2>&1", $output, $rc);
                if ($rc === 0) {
                    $output = array_filter($output);
                    if (count($output) === 2) {
                        $result = array_pop($output);
                    }
                }
            }
        }

        return $result;
    }
}
