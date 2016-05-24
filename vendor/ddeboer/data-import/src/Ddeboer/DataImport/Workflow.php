<?php

namespace Ddeboer\DataImport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
use Ddeboer\DataImport\Exception\ExceptionInterface;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Ddeboer\DataImport\Reader\ReaderInterface;
use Ddeboer\DataImport\Writer\WriterInterface;
use Ddeboer\DataImport\Filter\FilterInterface;
use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;
use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;

use DateTime;

/**
 * A mediator between a reader and one or more writers and converters
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class Workflow
{
    /**
     * Reader
     *
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * skipItemOnError
     *
     * @var boolean
     */
    protected $skipItemOnFailure = false;

    /**
     * Array of writers
     *
     * @var array|WriterInterface[]
     */
    protected $writers = array();

    /**
     * Array of value converters
     *
     * @var array
     */
    protected $valueConverters = array();

    /**
     * Array of item converters
     *
     * @var array|ItemConverterInterface[]
     */
    protected $itemConverters = array();

    /**
     * Array of filters
     *
     * @var \SplPriorityQueue|FilterInterface[]
     */
    protected $filters = array();

    /**
     * Array of filters that will be checked after data conversion
     *
     * @var \SplPriorityQueue|FilterInterface[]
     */
    protected $afterConversionFilters = array();

    /**
     * Identifier for the Import/Export
     *
     * @var string|null
     */
    protected $name = null;

    /**
     * Construct a workflow
     *
     * @param ReaderInterface $reader
     * @param LoggerInterface $logger
     * @param string $name
     */
    public function __construct(ReaderInterface $reader, LoggerInterface $logger = null, $name = null)
    {

        if (null !== $name && !is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Name identifier should be a string. Given: '%s'",
                    (is_object($name) ? get_class($name) : gettype($name))
                )
            );
        }

        $this->name = $name;
        $this->reader = $reader;
        $this->logger = $logger ? $logger : new NullLogger();
        $this->filters = new \SplPriorityQueue();
        $this->afterConversionFilters =  new \SplPriorityQueue();
    }

    /**
     * Add a filter to the workflow
     *
     * A filter decides whether an item is accepted into the import process.
     *
     * @param FilterInterface $filter   Filter
     * @param int             $priority Priority (optional)
     *
     * @return Workflow
     */
    public function addFilter(FilterInterface $filter, $priority = null)
    {
        $this->filters->insert($filter, $priority ?: $filter->getPriority());

        return $this;
    }

    /**
     * Add after conversion filter
     *
     * @param FilterInterface $filter   Filter
     * @param int             $priority Priority (optional)
     *
     * @return Workflow
     */
    public function addFilterAfterConversion(FilterInterface $filter, $priority = null)
    {
        $this->afterConversionFilters->insert($filter, $priority ?: $filter->getPriority());

        return $this;
    }

    /**
     * Add a writer to the workflow
     *
     * A writer takes a filtered and converted item, and writes that to, e.g.,
     * a database or CSV file.
     *
     * @param WriterInterface $writer
     *
     * @return $this
     */
    public function addWriter(WriterInterface $writer)
    {
        $this->writers[] = $writer;

        return $this;
    }

    /**
     * Add a value converter to the workflow
     *
     * @param string                  $field     Field
     * @param ValueConverterInterface $converter ValueConverter
     *
     * @return $this
     */
    public function addValueConverter($field, ValueConverterInterface $converter)
    {
        $this->valueConverters[$field][] = $converter;

        return $this;
    }

    /**
     * Add an item converter to the workflow
     *
     * @param ItemConverterInterface $converter Item converter
     *
     * @return $this
     */
    public function addItemConverter(ItemConverterInterface $converter)
    {
        $this->itemConverters[] = $converter;

        return $this;
    }

    /**
     * Add a mapping to the workflow
     *
     * If we can get the field names from the reader, they are just to check the
     * $fromField against.
     *
     * @param string       $fromField Field to map from
     * @param string|array $toField   Field or array to map to
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addMapping($fromField, $toField)
    {
        if (count($this->reader->getFields()) > 0) {
            if (!in_array($fromField, $this->reader->getFields())) {
                throw new \InvalidArgumentException("$fromField is an invalid field");
            }
        }

        $this->getMappingItemConverter()->addMapping($fromField, $toField);

        return $this;
    }

    /**
     * Process the whole import workflow
     *
     * 1. Prepare the added writers.
     * 2. Ask the reader for one item at a time.
     * 3. Filter each item.
     * 4. If the filter succeeds, convert the item’s values using the added
     *    converters.
     * 5. Write the item to each of the writers.
     *
     * @throws ExceptionInterface
     * @return Result Object Containing Workflow Results
     */
    public function process()
    {
        $count      = 0;
        $exceptions = array();
        $startTime  = new DateTime;

        // Prepare writers
        foreach ($this->writers as $writer) {
            $writer->prepare();
        }

        // Read all items
        foreach ($this->reader as $rowIndex => $item) {
            try {
                // Apply filters before conversion
                if (!$this->filterItem($item, $this->filters)) {
                    continue;
                }

                $convertedItem = $this->convertItem($item);
                if (!$convertedItem) {
                    continue;
                }

                // Apply filters after conversion
                if (!$this->filterItem($convertedItem, $this->afterConversionFilters)) {
                    continue;
                }

                foreach ($this->writers as $writer) {
                    $writer->writeItem($convertedItem);
                }

            } catch(ExceptionInterface $e) {
                if ($this->skipItemOnFailure) {
                    $exceptions[$rowIndex] = $e;
                    $this->logger->error($e->getMessage());
                } else {
                    throw $e;
                }
            }
            $count++;
        }

        // Finish writers
        foreach ($this->writers as $writer) {
            $writer->finish();
        }

        return new Result($this->name, $startTime, new DateTime, $count, $exceptions);
    }

    /**
     * Apply the filter chain to the input; if at least one filter fails,
     * the chain fails
     *
     * @param mixed                               $item    Item
     * @param \SplPriorityQueue|FilterInterface[] $filters Filters
     *
     * @return boolean
     */
    protected function filterItem($item, \SplPriorityQueue $filters)
    {
        // SplPriorityQueue must be cloned because it is a stack and thus drops
        // elements each time it is iterated over.
        foreach (clone $filters as $filter) {
            if (false == $filter->filter($item)) {
                return false;
            }
        }

        // Return true if no filters failed
        return true;
    }

    /**
     * Convert the item
     *
     * @param string $item Original item values
     *
     * @return array                   Converted item values
     * @throws UnexpectedTypeException
     */
    protected function convertItem($item)
    {
        foreach ($this->itemConverters as $converter) {
            $item = $converter->convert($item);
            if ($item && !(is_array($item) || ($item instanceof \ArrayAccess && $item instanceof \Traversable))) {
                throw new UnexpectedTypeException($item, 'false or array');
            }

            if (!$item) {
                return $item;
            }
        }

        if ($item && !(is_array($item) || ($item instanceof \ArrayAccess && $item instanceof \Traversable))) {
            throw new UnexpectedTypeException($item, 'false or array');
        }

        foreach ($this->valueConverters as $property => $converters) {
            // isset() returns false when value is null, so we need
            // array_key_exists() too. Combine both to have best performance,
            // as isset() is much faster.
            if (isset($item[$property]) || array_key_exists($property, $item)) {
                foreach ($converters as $converter) {
                    $item[$property] = $converter->convert($item[$property]);
                }
            }
        }

        return $item;
    }

    /**
     * Get item converter that takes care of mapping
     *
     * @return MappingItemConverter
     */
    protected function getMappingItemConverter()
    {
        // Find mapping item converter
        $converters = \array_filter(
            $this->itemConverters,
            function ($converter) {
                return $converter instanceof MappingItemConverter;
            }
        );

        if (count($converters) > 0) {
            // Return first mapping item converter that we encounter
            reset($converters);
            $converter = current($converters);
        } else {
            // Create default converter
            $converter = new MappingItemConverter();
            $this->addItemConverter($converter);
        }

        return $converter;
    }

    /**
     * Set skipItemOnFailure.
     *
     * @param boolean $skipItemOnFailure then true skip current item on process exception and log the error
     *
     * @return $this
     */
    public function setSkipItemOnFailure($skipItemOnFailure)
    {
        $this->skipItemOnFailure = $skipItemOnFailure;

        return $this;
    }
}
