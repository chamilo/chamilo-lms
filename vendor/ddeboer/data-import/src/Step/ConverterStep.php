<?php

namespace Ddeboer\DataImport\Step;

use Ddeboer\DataImport\Step;
use Ddeboer\DataImport\Exception\UnexpectedTypeException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ConverterStep implements Step
{
    /**
     * @var callable[]
     */
    private $converters;

    /**
     * @param array $converters
     */
    public function __construct(array $converters = [])
    {
        foreach ($converters as $converter) {
            $this->add($converter);
        }
    }

    /**
     * @param callable $converter
     *
     * @return $this
     */
    public function add(callable $converter)
    {
        $this->converters[] = $converter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(&$item)
    {
        foreach ($this->converters as $converter) {
            $item = call_user_func($converter, $item);
        }

        return true;
    }
}
