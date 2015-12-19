<?php

namespace Ddeboer\DataImport\ItemConverter;

class MappingItemConverter implements ItemConverterInterface
{
    /**
     * @var array
     */
    protected $mappings = array();

    /**
     * Constructor
     *
     * @param array $mappings Mappings (optional)
     */
    public function __construct(array $mappings = array())
    {
        $this->setMappings($mappings);
    }

    /**
     * Add a mapping
     *
     * @param string       $from Field to map from
     * @param string|array $to   Field name or array to map to
     *
     * @return $this
     */
    public function addMapping($from, $to)
    {
        $this->mappings[$from] = $to;

        return $this;
    }

    /**
     * Set mappings
     *
     * @param array $mappings
     *
     * @return $this
     */
    public function setMappings(array $mappings)
    {
        $this->mappings = array();

        foreach ($mappings as $from => $to) {
            $this->addMapping($from, $to);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($input)
    {
        foreach ($this->mappings as $from => $to) {
            $input = $this->applyMapping($input, $from, $to);
        }

        return $input;
    }

    /**
     * Applies a mapping to an item
     *
     * @param array  $item
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    protected function applyMapping(array $item, $from, $to)
    {
        // skip fields that dont exist
        if (!isset($item[$from]) && !array_key_exists($from, $item)) {
            return $item;
        }

        // skip equal fields
        if ($from == $to) {
            return $item;
        }

        // standard renaming
        if (!is_array($to)) {
            $item[$to] = $item[$from];
            unset($item[$from]);

            return $item;
        }

        // recursive renaming of an array
        foreach ($to as $nestedFrom => $nestedTo) {
            $item[$from] = $this->applyMapping($item[$from], $nestedFrom, $nestedTo);
        }

        return $item;
    }
}
