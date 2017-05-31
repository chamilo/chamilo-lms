<?php

namespace Ddeboer\DataImport\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;

/**
 * Convert a value in a specific charset
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class CharsetValueConverter
{
    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $inCharset;

    /**
     * @param string $charset   Charset to convert values to
     * @param string $inCharset Charset of input values
     */
    public function __construct($charset, $inCharset = 'UTF-8')
    {
        $this->charset = $charset;
        $this->inCharset = $inCharset;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($input)
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($input, $this->charset, $this->inCharset);
        }

        if (function_exists('iconv')) {
            return iconv($this->inCharset, $this->charset, $input);
        }

        throw new \RuntimeException('Could not convert the charset. Please install the mbstring or iconv extension!');
    }
}
