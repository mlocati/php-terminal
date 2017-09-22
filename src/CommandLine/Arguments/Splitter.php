<?php

namespace MLocati\Terminal\CommandLine\Arguments;

use MLocati\Terminal\Exception\InvalidCommandLineException;
use MLocati\Terminal\Exception\QuotesMismatchException;

/**
 * Class to split a full command line into its arguments.
 */
class Splitter
{
    /**
     * Allowed argument separators for POSIX systems.
     *
     * @var string
     */
    const POSIX_WHITESPACES = " \t\r\n\v";

    /**
     * Allowed argument separators for Windows systems.
     *
     * @var string
     */
    const WINDOWS_WHITESPACES = " \t";

    /**
     * Split a full command line into a list of arguments, assuming the current operating system (POSIX or Windows).
     *
     * @param string|mixed $commandLine The command line to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLine is not valid (not a string, empty string)
     * @throws QuotesMismatchException throws a MLocati\Terminal\Exception\QuotesMismatchException is $commandLine contain mismatched quotes
     *
     * @return array
     */
    public function splitCommandLine($commandLine)
    {
        return DIRECTORY_SEPARATOR === '\\' ? $this->splitCommandLine_Windows($commandLine) : $this->splitCommandLine_Posix($commandLine);
    }

    /**
     * Split a full command line into a list of arguments, for POSIX systems.
     *
     * @param string|mixed $commandLine The command line to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLine is not valid (not a string, empty string)
     * @throws QuotesMismatchException throws a MLocati\Terminal\Exception\QuotesMismatchException is $commandLine contain mismatched quotes
     *
     * @return array
     */
    public function splitCommandLine_Posix($commandLine)
    {
        $result = $this->splitCommandLineArguments_Posix($commandLine);
        if ($result === []) {
            throw new InvalidCommandLineException($commandLine);
        }

        return $result;
    }

    /**
     * Split a full command line into a list of arguments, for Windows systems.
     *
     * @param string|mixed $commandLine The command line to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLine is not valid (not a string, empty string)
     *
     * @return array
     */
    public function splitCommandLine_Windows($commandLine)
    {
        if (!is_string($commandLine) || trim($commandLine) === '') {
            throw new InvalidCommandLineException($commandLine);
        }
        $singleLine = preg_replace('/\^[\r\n]+/', '', $commandLine);
        $position = 0;
        while (strpos(static::WINDOWS_WHITESPACES, $singleLine[$position]) !== false) {
            ++$position;
        }
        if ($singleLine[$position] === '"') {
            $start = $position + 1;
            $end = strpos($singleLine, '"', $start);
            if ($end === false) {
                $end = strlen($singleLine);
            }
            $firstArgument = substr($singleLine, $start, $end - $start);
            $position = $end + 1;
        } else {
            $start = $position;
            $whitespacePosition = false;
            $whitespaces = static::WINDOWS_WHITESPACES;
            $numWhitespaces = strlen(static::WINDOWS_WHITESPACES);
            for ($i = 0; $i < $numWhitespaces; ++$i) {
                $p = strpos($singleLine, $whitespaces[$i], $start + 1);
                if ($p !== false && ($whitespacePosition === false || $whitespacePosition > $p)) {
                    $whitespacePosition = $p;
                }
            }
            if ($whitespacePosition === false) {
                $firstArgument = substr($singleLine, $start);
                $position = strlen($singleLine);
            } else {
                $firstArgument = substr($singleLine, $start, $whitespacePosition - $start);
                $position = $whitespacePosition + 1;
            }
            $firstArgument = str_replace('"', '', $firstArgument);
        }
        if (isset($singleLine[$position])) {
            return array_merge([$firstArgument], $this->splitCommandLineArguments_Windows(substr($singleLine, $position)));
        } else {
            return [$firstArgument];
        }
    }

    /**
     * Split command line arguments (that is, the ones following the command name), assuming the current operating system (POSIX or Windows).
     *
     * @param string|mixed $commandLineArguments The command line arguments to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLineArguments is not a string
     * @throws QuotesMismatchException throws a MLocati\Terminal\Exception\QuotesMismatchException is $commandLineArguments contain mismatched quotes
     *
     * @return array
     */
    public function splitCommandLineArguments($commandLineArguments)
    {
        return DIRECTORY_SEPARATOR === '\\' ? $this->splitCommandLineArguments_Windows($commandLineArguments) : $this->splitCommandLineArguments_Posix($commandLineArguments);
    }

    /**
     * Split command line arguments (that is, the ones following the command name), for POSIX systems.
     *
     * @param string|mixed $commandLineArguments The command line arguments to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLineArguments is not a string
     * @throws QuotesMismatchException throws a MLocati\Terminal\Exception\QuotesMismatchException is $commandLineArguments contain mismatched quotes
     *
     * @return array
     */
    public function splitCommandLineArguments_Posix($commandLineArguments)
    {
        if (!is_string($commandLineArguments)) {
            throw new InvalidCommandLineException($commandLineArguments);
        }
        $result = [];
        $singleLine = preg_replace('/\\\\[\r\n]+/', '', $commandLineArguments);
        if (trim($singleLine) !== '') {
            $position = 0;
            $length = strlen($singleLine);
            while ($position < $length) {
                while ($position < $length) {
                    if (strpos(static::POSIX_WHITESPACES, $singleLine[$position]) === false) {
                        break;
                    }
                    ++$position;
                    if ($position === $length) {
                        break 2;
                    }
                }
                $quoter = null;
                $argument = '';
                $chunk = '';
                for (; $position < $length; ++$position) {
                    $char = $singleLine[$position];
                    if ($quoter === null && strpos(static::POSIX_WHITESPACES, $char) !== false) {
                        break;
                    }
                    $nextChar = $position + 1 < $length ? $singleLine[$position + 1] : null;
                    if ($quoter !== null) {
                        if ($char === '\\' && ($nextChar === $quoter || $nextChar === '\\')) {
                            ++$position;
                            $chunk .= $nextChar;
                            continue;
                        }
                    }
                    if ($char === $quoter) {
                        $argument .= $quoter === '"' ? stripcslashes($chunk) : $chunk;
                        $quoter = null;
                        $chunk = '';
                        continue;
                    }
                    if ($quoter !== "'" && $char === '\\' && $nextChar !== null) {
                        /*
                        if ($nextChar === 'u' && $position + 6 <= $length) {
                            $jsonDecoded = @json_decode('"' . substr($singleLine, $position, 6) . '"');
                            if ($jsonDecoded !== null) {
                                $argument .= stripslashes($chunk) . $jsonDecoded;
                                $chunk = '';
                                $position += 5;
                                continue;
                            }
                        }
                        */
                        $chunk .= $char . $nextChar;
                        ++$position;
                        continue;
                    }
                    if ($quoter === null && ($char === '"' || $char === "'")) {
                        $quoter = $char;
                        $argument .= stripslashes($chunk);
                        $chunk = '';
                        continue;
                    }
                    $chunk .= $char;
                }
                if ($quoter !== null) {
                    throw new QuotesMismatchException($commandLineArguments);
                }
                if ($chunk !== '') {
                    $argument .= stripcslashes($chunk);
                }
                $result[] = $argument;
            }
        }

        return $result;
    }

    /**
     * Split command line arguments (that is, the ones following the command name), for Windows systems.
     *
     * @param string|mixed $commandLineArguments The command line arguments to be parsed
     *
     * @throws InvalidCommandLineException throws a MLocati\Terminal\Exception\InvalidCommandLineException is $commandLineArguments is not a string
     *
     * @return array
     */
    public function splitCommandLineArguments_Windows($commandLineArguments)
    {
        if (!is_string($commandLineArguments)) {
            throw new InvalidCommandLineException($commandLineArguments);
        }

        return $this->splitCommandLineArguments_Windows_2008($commandLineArguments);
    }

    /**
     * Split command line arguments (that is, the ones following the command name), for Windows systems - using the pre-2008 syntax.
     *
     * @param string $commandLineArguments The command line arguments to be parsed
     *
     * @return array
     */
    private function splitCommandLineArguments_Windows_Pre2008($commandLineArguments)
    {
        $length = strlen($commandLineArguments);
        $numberOfQuotes = 0;
        $numberOfBackslashes = 0;
        $result = [];
        $position = 0;
        $argument = null;
        while ($position < $length) {
            $char = $commandLineArguments[$position];
            if ($numberOfQuotes === 0 && strpos(static::WINDOWS_WHITESPACES, $char) !== false) {
                if ($argument !== null) {
                    $result[] = $argument;
                    $argument = null;
                }
                while (true) {
                    ++$position;
                    if ($position === $length) {
                        break 2;
                    }
                    $char = $commandLineArguments[$position];
                    if (strpos(static::WINDOWS_WHITESPACES, $char) === false) {
                        break;
                    }
                }
                $numberOfBackslashes = 0;
                $argument = '';
            }
            if ($char === '\\') {
                ++$numberOfBackslashes;
                $argument .= $char;
                ++$position;
            } elseif ($char === '"') {
                if ($numberOfBackslashes % 2 === 0) {
                    if ($numberOfBackslashes >= 2) {
                        $argument = substr($argument, 0, -(int) ($numberOfBackslashes / 2));
                    }
                    ++$numberOfQuotes;
                } else {
                    $argument = substr($argument, 0, -1 - (int) ($numberOfBackslashes / 2));
                    $argument .= '"';
                }
                ++$position;
                $numberOfBackslashes = 0;
                while ($position < $length) {
                    $char = $commandLineArguments[$position];
                    if ($char !== '"') {
                        break;
                    }
                    ++$numberOfQuotes;
                    if ($numberOfQuotes === 3) {
                        $argument .= '"';
                        $numberOfQuotes = 0;
                    }
                    ++$position;
                }
                if ($numberOfQuotes === 2) {
                    $numberOfQuotes = 0;
                }
            } else {
                $argument .= $char;
                $numberOfBackslashes = 0;
                ++$position;
            }
        }
        if ($argument !== null) {
            $result[] = $argument;
        }

        return $result;
    }

    /**
     * Split command line arguments (that is, the ones following the command name), for Windows systems - using the 2008 syntax.
     *
     * @param string $commandLineArguments The command line arguments to be parsed
     *
     * @return array
     */
    private function splitCommandLineArguments_Windows_2008($commandLineArguments)
    {
        $length = strlen($commandLineArguments);
        $numberOfQuotes = 0;
        $numberOfBackslashes = 0;
        $result = [];
        $position = 0;
        $argument = null;
        while ($position < $length) {
            $char = $commandLineArguments[$position];
            if ($numberOfQuotes === 0 && strpos(static::WINDOWS_WHITESPACES, $char) !== false) {
                if ($argument !== null) {
                    $result[] = $argument;
                    $argument = null;
                }
                while (true) {
                    ++$position;
                    if ($position === $length) {
                        break 2;
                    }
                    $char = $commandLineArguments[$position];
                    if (strpos(static::WINDOWS_WHITESPACES, $char) === false) {
                        break;
                    }
                }
                $numberOfBackslashes = 0;
                $argument = '';
            }
            if ($char === '\\') {
                ++$numberOfBackslashes;
                $argument .= $char;
                ++$position;
            } elseif ($char === '"') {
                if ($numberOfBackslashes % 2 === 0) {
                    if ($numberOfBackslashes >= 2) {
                        $argument = substr($argument, 0, -(int) ($numberOfBackslashes / 2));
                    }
                    ++$numberOfQuotes;
                } else {
                    $argument = substr($argument, 0, -1 - (int) ($numberOfBackslashes / 2));
                    $argument .= '"';
                }
                ++$position;
                $numberOfBackslashes = 0;
                while ($position < $length) {
                    $char = $commandLineArguments[$position];
                    if ($char !== '"') {
                        break;
                    }
                    ++$numberOfQuotes;
                    if ($numberOfQuotes === 3) {
                        $argument .= '"';
                        $numberOfQuotes = 1;
                    }
                    ++$position;
                }
                if ($numberOfQuotes === 2) {
                    $numberOfQuotes = 0;
                }
            } else {
                $argument .= $char;
                $numberOfBackslashes = 0;
                ++$position;
            }
        }
        if ($argument !== null) {
            $result[] = $argument;
        }

        return $result;
    }
}
