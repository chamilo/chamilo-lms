<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Filter;

use MediaVorus\Media\MediaInterface;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class MediaType implements FilterInterface
{
    protected $type;

    /**
     * Filter on Media Type
     *
     * @param string $type One of the \MediaVorus\Media\Media::TYPE_* constants
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $type = $this->type;

        return function($key, MediaInterface $media) use ($type) {
            return $media->getType() === $type;
        };
    }
}
