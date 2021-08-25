<?php

namespace Ddeboer\DataImport\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedValueException;

/**
 * @author Grégoire Paris
 */
class MappingValueConverter
{
    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function __invoke($input)
    {
        if (!isset($this->mapping[$input])) {
            throw new UnexpectedValueException(sprintf(
                'Cannot find mapping for value "%s"',
                $input
            ));
        }

        return $this->mapping[$input];
    }
}
