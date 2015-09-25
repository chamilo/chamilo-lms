<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Media;

class Document extends Image
{
    /**
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE_DOCUMENT;
    }
}
