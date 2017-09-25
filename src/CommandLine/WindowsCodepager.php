<?php

namespace MLocati\Terminal\CommandLine;

use MLocati\Terminal\Exception\CodepageConversionException;
use MLocati\Terminal\Exception\WindowsCodepageDetectionFailed;

class WindowsCodepager
{
    /**
     * The UTF-8 codepage.
     *
     * @var int
     */
    const UTF8_CODEPAGE = 65001;

    /**
     * The Windows codepage.
     *
     * @var int|null
     */
    private $codepage = null;

    /**
     * Get the Windows codepage.
     *
     * @throws WindowsCodepageDetectionFailed throws a WindowsCodepageDetectionFailed exception if the automatic determination of the Windows codepage failed
     *
     * @return int
     */
    public function getCodepage()
    {
        if ($this->codepage === null) {
            if (PHP_VERSION_ID >= 70100) {
                $this->codepage = sapi_windows_cp_get();
            } else {
                $rc = -1;
                $output = [];
                @exec('chcp 2>&1', $output, $rc);
                if ($rc !== 0) {
                    throw new WindowsCodepageDetectionFailed(trim(implode("\n", $output)));
                }
                $output = array_values(array_filter($output));
                if (count($output) !== 1 || !preg_match('/(\d+)$/', $output[0], $matches)) {
                    throw new WindowsCodepageDetectionFailed(sprintf("Failed to detect the Windows codepage starting from this string:\n%s", implode("\n", $output)));
                }
                $this->codepage = (int) $matches[1];
            }
        }

        return $this->codepage;
    }

    /**
     * Set the Windows codepage.
     *
     * @param int|null|false $codepage if falsy: we'll detect the current Windows codepage
     *
     * @return $this
     */
    public function setCodepage($codepage)
    {
        if ($codepage) {
            $this->codepage = (int) $codepage;
        } else {
            $this->codepage = null;
        }
    }

    /**
     * Decode a string encoded in the configured codepage to an UTF-8 string.
     *
     * @param string $string The string to be converted
     *
     * @throws CodepageConversionException throws a CodepageConversionException exception if the conversion fails
     *
     * @return string
     */
    public function decode($string)
    {
        $codepage = $this->getCodepage();
        if ($codepage === static::UTF8_CODEPAGE) {
            $result = $string;
        } else {
            $decoded = @iconv("CP{$codepage}", 'UTF-8', $string);
            if ($decoded === false) {
                throw new CodepageConversionException(CodepageConversionException::OPERATION_DECODING, $string);
            }
            $result = $decoded;
        }

        return $result;
    }

    /**
     * Encode an UTF-8 string to the configured codepage.
     *
     * @param string $string The string to be converted
     *
     * @throws CodepageConversionException throws a CodepageConversionException exception if the conversion fails
     *
     * @return string
     */
    public function encode($string)
    {
        $codepage = $this->getCodepage();
        if ($codepage === static::UTF8_CODEPAGE) {
            $result = $string;
        } else {
            $encoded = @iconv('UTF-8', "CP{$codepage}", $string);
            if ($encoded === false) {
                throw new CodepageConversionException(CodepageConversionException::OPERATION_ENCODING, $string);
            }
            $result = $encoded;
        }

        return $result;
    }
}
