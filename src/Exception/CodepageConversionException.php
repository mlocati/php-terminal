<?php

namespace MLocati\Terminal\Exception;

use MLocati\Terminal\Exception;

/**
 * Exception thrown when codepage conversion fails.
 */
class CodepageConversionException extends Exception
{
    /**
     * The exception occurred while decoding the string.
     *
     * @var int
     */
    const OPERATION_DECODING = 1;

    /**
     * The exception occurred while encoding the string.
     *
     * @var int
     */
    const OPERATION_ENCODING = 1;

    /**
     * The failing operation (one of the CodepageConversionException::OPERATION_... constants).
     *
     * @var int
     */
    protected $failingOperation;

    /**
     * The string that couldn't be converted.
     *
     * @var string
     */
    protected $failingString;

    /**
     * Initialize the instance.
     *
     * @param int $failingOperation when the exception occurred (one of the CodepageConversionException::OPERATION_... constants)
     * @param string $failingString the string that couldn't be converted
     */
    public function __construct($failingOperation, $failingString)
    {
        $this->failingOperation = (int) $failingOperation;
        switch ($this->failingOperation) {
            case static::OPERATION_DECODING:
                parent::__construct('Failed to decode a string from the Windows codepage');
                break;
            case static::OPERATION_DECODING:
                parent::__construct('Failed to encode a string to the Windows codepage');
                break;
        }
        $this->failingString = $failingString;
    }

    /**
     * Get the failing operation (one of the CodepageConversionException::OPERATION_... constants).
     *
     * @return int
     */
    public function getFailingOperation()
    {
        return $this->failingOperation;
    }

    /**
     * Get the string that couldn't be converted.
     *
     * @return string
     */
    public function getFailingString()
    {
        return $this->failingString;
    }
}
