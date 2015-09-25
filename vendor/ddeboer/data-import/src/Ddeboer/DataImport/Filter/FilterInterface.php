<?php

namespace Ddeboer\DataImport\Filter;

/**
 * A filter decides whether an item is accepted into the import workflow
 */
interface FilterInterface
{
    /**
     * Filter input
     *
     * @param array $item Input
     *
     * @return boolean If false is returned, the workflow will skip the input
     */
    public function filter(array $item);

    /**
     * Get filter priority (higher number means higher priority)
     *
     * @return int
     */
    public function getPriority();
}
