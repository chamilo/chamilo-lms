<?php

namespace Flint\Config\Normalizer;

use Pimple;

/**
 * @package Flint
 */
class PimpleAwareNormalizer extends \Flint\PimpleAware implements NormalizerInterface
{
    const PLACEHOLDER = '{%%|%([a-z0-9_.]+)%}';

    /**
     * @param Pimple $pimple
     */
    public function __construct(Pimple $pimple = null)
    {
        $this->setPimple($pimple);
    }

    /**
     * @param  string $contents
     * @return string
     */
    public function normalize($contents)
    {
        return preg_replace_callback(static::PLACEHOLDER, array($this, 'callback'), $contents);
    }

    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return '%%';
        }

        return $this->scalarToString($this->pimple[$matches[1]]);
    }

    /**
     * @param  mixed $value
     * @return mixed
     */
    protected function scalarToString($value)
    {
        switch (gettype($value)) {
            case 'resource':
            case 'object':
                throw new \RuntimeException('Unable to replace placeholder if its replacement is an object or resource.');
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'NULL':
                return 'null';
            default:
                return (string) $value;
        }
    }
}
