<?php

namespace MLocati\Terminal\CommandLine\Arguments;

use MLocati\Terminal\CommandLine\WindowsCodepager;
use MLocati\Terminal\Exception\InvalidCommandLineArgumentsException;

/**
 * Class to join arguments into a full command line.
 */
class Joiner
{
    /**
     * The WindowsCodepager instance to be used to encode Windows commands from UTF-8 to the system codepage.
     *
     * @var WindowsCodepager|null
     */
    protected $windowsCodepager;

    /**
     * Escape the joined strings for the current code page on Windows systems?
     *
     * @var bool
     */
    protected $enableEscapeForWindowsCodepage = true;

    /**
     * Set the WindowsCodepager instance to be used to encode Windows commands from UTF-8 to the system codepage.
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
     * Get the WindowsCodepager instance to be used to encode Windows commands from UTF-8 to the system codepage.
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
     * Escape the joined strings for the current code page on Windows systems?
     *
     * @param bool $escape
     *
     * @return $this
     */
    public function setEnableEscapeForWindowsCodepage($escape)
    {
        $this->enableEscapeForWindowsCodepage = (bool) $escape;

        return $this;
    }

    /**
     * Escape the joined strings for the current code page on Windows systems?
     *
     * @return bool
     */
    public function isEscapeForWindowsCodepageEnabled()
    {
        return $this->enableEscapeForWindowsCodepage;
    }

    /**
     * Join the arguments into a full command, assuming the current operating system (POSIX or Windows).
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLine(array $arguments)
    {
        return DIRECTORY_SEPARATOR === '\\' ? $this->joinCommandLine_Windows($arguments) : $this->joinCommandLine_Posix($arguments);
    }

    /**
     * Join the arguments into a full command, for POSIX systems.
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLine_Posix(array $arguments)
    {
        $result = $this->joinCommandLineArguments_Posix($arguments);
        if ($result === '' || $result === "''" || strpos($result, "'' ") === 0) {
            throw new InvalidCommandLineArgumentsException($arguments);
        }

        return $result;
    }

    /**
     * Join the arguments into a full command, for Windows systems.
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLine_Windows(array $arguments)
    {
        if (count($arguments) === 0) {
            throw new InvalidCommandLineArgumentsException($arguments);
        }
        $temp = $arguments;
        $result = array_shift($temp);
        if (trim($result) === '') {
            throw new InvalidCommandLineArgumentsException($arguments);
        }
        if (strpos($result, '"') !== false) {
            throw new InvalidCommandLineArgumentsException($arguments, "The command can't contain double quotes.");
        }
        if (strpbrk($result, " \t") !== false) {
            $result = '"' . $result . '"';
        }

        $wasEscaping = $this->isEscapeForWindowsCodepageEnabled();
        $this->setEnableEscapeForWindowsCodepage(false);
        try {
            $args = $this->joinCommandLineArguments_Windows($temp);
        } finally {
            $this->setEnableEscapeForWindowsCodepage($wasEscaping);
        }
        if ($args !== '') {
            $result .= ' ' . $args;
        }

        if ($this->isEscapeForWindowsCodepageEnabled()) {
            $result = $this->getWindowsCodepager()->encode($result);
        }

        return $result;
    }

    /**
     * Join the command line arguments (that is, the ones following the command name) into a string, assuming the current operating system (POSIX or Windows).
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLineArguments(array $arguments)
    {
        return DIRECTORY_SEPARATOR === '\\' ? $this->joinCommandLineArguments_Windows($arguments) : $this->joinCommandLineArguments_Posix($arguments);
    }

    /**
     * Join the command line arguments (that is, the ones following the command name) into a string, for POSIX systems.
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLineArguments_Posix(array $arguments)
    {
        $escaped = [];
        foreach ($arguments as $argument) {
            $argument = (string) $argument;
            if (preg_match('/^[\w\/:\-=0x80-0xff]+$/', $argument)) {
                $escaped[] = $argument;
            } else {
                $cmd = "'";
                $l = strlen($argument);
                for ($x = 0; $x < $l; ++$x) {
                    $char = $argument[$x];
                    if (ord($char) >= 0x80) {
                        $cmd .= $char;
                    } elseif ($char === "'") {
                        $cmd .= "'\''";
                    } else {
                        $cmd .= $char;
                    }
                }
                $cmd .= "'";
                $escaped[] = $cmd;
            }
        }

        return implode(' ', $escaped);
    }

    /**
     * Join the command line arguments (that is, the ones following the command name) into a string, for Windows systems.
     *
     * @param string[] $arguments the arguments to be concatenated
     *
     * @return string
     */
    public function joinCommandLineArguments_Windows(array $arguments)
    {
        $escaped = [];
        foreach ($arguments as $argument) {
            // https://blogs.msdn.microsoft.com/twistylittlepassagesallalike/2011/04/23/everyone-quotes-command-line-arguments-the-wrong-way/
            if (strpbrk($argument, " \t\n\v\"") === false) {
                $cmd = strtr(
                    $argument,
                    [
                        '^' => '^^',
                        '>' => '^>',
                        '<' => '^<',
                        '|' => '^|',
                        '&' => '^&',
                    ]
                );
            } else {
                $length = strlen($argument);
                $cmd = '"';
                for ($position = 0; ; ++$position) {
                    $numberOfBackslashes = 0;
                    while ($position < $length) {
                        $char = $argument[$position];
                        if ($char !== '\\') {
                            break;
                        }
                        ++$position;
                        ++$numberOfBackslashes;
                    }
                    if ($position === $length) {
                        $cmd .= str_repeat('\\', $numberOfBackslashes * 2);
                        break;
                    }
                    if ($char === '"') {
                        $cmd .= str_repeat('\\', 1 + $numberOfBackslashes * 2);
                    } else {
                        $cmd .= str_repeat('\\', $numberOfBackslashes);
                    }
                    $cmd .= $char;
                }
                $cmd .= '"';
            }
            $escaped[] = $cmd;
        }
        $result = implode(' ', $escaped);
        if ($this->isEscapeForWindowsCodepageEnabled()) {
            $result = $this->getWindowsCodepager()->encode($result);
        }

        return $result;
    }
}
