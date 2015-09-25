<?php
namespace Ddeboer\DataImport\ItemConverter;

use Ddeboer\DataImport\ItemConverter\MappingItemConverter;

/**
 * An item converter that takes an input containing nested arrays from a reader, and returns a modified item based on
 * mapped keys.
 *
 * @author Adam Paterson <hello@adampaterson.co.uk>
 */
class NestedMappingItemConverter extends MappingItemConverter
{
    /**
     * @var string
     */
    protected $nestKey;

    /**
     * @param array $mappings
     * @param string $nestKey
     */
    public function __construct($nestKey, array $mappings = array())
    {
        parent::__construct($mappings);
        $this->nestKey = $nestKey;
    }

    /**
     * @param array $item
     * @param string $from
     * @param string $to
     * @return array
     */
    protected function applyMapping(array $item, $from, $to)
    {
        if ($from !== $this->nestKey) {
            return parent::applyMapping($item, $from, $to);
        }

        foreach ($item[$this->nestKey] as $key => $nestedItem) {
            foreach ($to as $nestedFrom => $nestedTo) {
                $nestedItem = parent::applyMapping($nestedItem, $nestedFrom, $nestedTo);
            }

            $item[$this->nestKey][$key] = $nestedItem;
        }

        return $item;
    }
}