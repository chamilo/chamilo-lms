<?php

namespace Ddeboer\DataImport\ValueConverter;

/**
 * A value converter takes an input value from a reader, and returns a converted value
 *
 * The conversion can consist in mere filtering, but it is also possible to
 * do lookups, or give back specific objects.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
interface ValueConverterInterface
{
    /**
     * Convert a value
     *
     * @param mixed $input Input value
     *
     * @return mixed
     */
    public function convert($input);
}
