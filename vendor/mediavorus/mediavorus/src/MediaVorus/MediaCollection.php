<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus;

use Doctrine\Common\Collections\ArrayCollection;
use MediaVorus\Filter\FilterInterface;

class MediaCollection extends ArrayCollection
{

    /**
     * Filters a MediaCollection with Filters
     *
     * @param FilterInterface $filter
     * @param Boolean $invert_match
     * @return type
     */
    public function match(FilterInterface $filter, $invert_match = false)
    {
        list($with, $without) = $this->partition($filter->apply());

        return $invert_match ? $without : $with;
    }
}
