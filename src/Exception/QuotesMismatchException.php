<?php

namespace MLocati\Terminal\Exception;

use MLocati\Terminal\Exception;

/**
 * Exception thrown when a command line contains unclosed quotes.
 */
class QuotesMismatchException extends Exception
{
    /**
     * The failing command line.
     *
     * @var string
     */
    protected $commandLine;

    /**
     * Initialize the instance.
     *
     * @param string $commandLine The failing command line
     */
    public function __construct($commandLine)
    {
        $this->commandLine = $commandLine;
        parent::__construct(sprintf('Mismatching quotes found in command line: %s', $this->commandLine));
    }

    /**
     * Get the failing command line.
     *
     * @return string
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }
}
