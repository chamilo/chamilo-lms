<?php

namespace Ddeboer\DataImport\ValueConverter;

/**
 * Converts a nested array using a converter-map
 *
 * @author Christoph Rosse <christoph@rosse.at>
 */
class ArrayValueConverterMap implements ValueConverterInterface
{
    /**
     * @var array
     */
    private $converters;

    /**
     * Constructor
     *
     * @param array $converters
     */
    public function __construct(array $converters)
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritDoc}
     */
    public function convert($input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('Input of a ArrayValueConverterMap must be an array');
        }

        foreach ($input as $key => $item) {
            $input[$key] = $this->convertItem($item);
        }

        return $input;
    }

    /**
     * Convert an item of the array using the converter-map
     *
     * @param $item
     *
     * @return mixed
     */
    protected function convertItem($item)
    {
        foreach ($item as $key => $value) {
            if (!isset($this->converters[$key])) {
                continue;
            }

            foreach ($this->converters[$key] as $converter) {
                $item[$key] = $converter->convert($item[$key]);
            }
        }

        return $item;
    }
}
