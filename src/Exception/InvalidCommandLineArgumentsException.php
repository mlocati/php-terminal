<?php

namespace MLocati\Terminal\Exception;

use MLocati\Terminal\Exception;

/**
 * Exception thrown when the result of merging command line arguments is an empty string.
 */
class InvalidCommandLineArgumentsException extends Exception
{
    /**
     * The failing arguments.
     *
     * @var array
     */
    protected $arguments;

    /**
     * Initialize the instance.
     *
     * @param array $arguments the failing arguments
     * @param string $reason reason of the failure
     */
    public function __construct(array $arguments, $reason = '')
    {
        $this->arguments = $arguments;
        parent::__construct($reason ?: 'Merging the command line arguments led to an bad command.');
    }

    /**
     * Get the failing arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
