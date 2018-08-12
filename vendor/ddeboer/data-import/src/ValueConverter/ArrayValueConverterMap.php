<?php

namespace Ddeboer\DataImport\ValueConverter;

/**
 * Converts a nested array using a converter-map
 *
 * @author Christoph Rosse <christoph@rosse.at>
 */
class ArrayValueConverterMap
{
    /**
     * @var array
     */
    private $converters;

    /**
     * @param callable[] $converters
     */
    public function __construct(array $converters)
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($input)
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
                $item[$key] = call_user_func($converter, $item[$key]);
            }
        }

        return $item;
    }
}
