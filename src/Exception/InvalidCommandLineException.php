<?php

namespace MLocati\Terminal\Exception;

use MLocati\Terminal\Exception;

/**
 * Exception thrown when a specified command line is missing or invalid.
 */
class InvalidCommandLineException extends Exception
{
    /**
     * The failing command line.
     *
     * @var mixed
     */
    protected $commandLine;

    /**
     * Initialize the instance.
     *
     * @param mixed $commandLine
     */
    public function __construct($commandLine)
    {
        $this->commandLine = $commandLine;
        parent::__construct('Invalid/missing command line specified.');
    }

    /**
     * Get the failing command line.
     *
     * @return mixed
     */
    public function getCommandLine()
    {
        return $this->commandLine;
    }
}
