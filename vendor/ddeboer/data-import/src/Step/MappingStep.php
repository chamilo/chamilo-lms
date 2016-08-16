<?php

namespace Ddeboer\DataImport\Step;

use Ddeboer\DataImport\Exception\MappingException;
use Ddeboer\DataImport\Step;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MappingStep implements Step
{
    /**
     * @var array
     */
    private $mappings = [];

    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * @param array            $mappings
     * @param PropertyAccessor $accessor
     */
    public function __construct(array $mappings = [], PropertyAccessor $accessor = null)
    {
        $this->mappings = $mappings;
        $this->accessor = $accessor ?: new PropertyAccessor();
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return $this
     */
    public function map($from, $to)
    {
        $this->mappings[$from] = $to;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function process(&$item)
    {
        try {
            foreach ($this->mappings as $from => $to) {
                $value = $this->accessor->getValue($item, $from);
                $this->accessor->setValue($item, $to, $value);

                $from = str_replace(['[',']'], '', $from);

                // Check if $item is an array, because properties can't be unset.
                // So we don't call unset for objects to prevent side affects.
                if (is_array($item) && isset($item[$from])) {
                    unset($item[$from]);
                }
            }
        } catch (NoSuchPropertyException $exception) {
            throw new MappingException('Unable to map item', null, $exception);
        } catch (UnexpectedTypeException $exception) {
            throw new MappingException('Unable to map item', null, $exception);
        }
    }
}
