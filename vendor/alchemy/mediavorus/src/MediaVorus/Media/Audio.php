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

use JMS\Serializer\Annotation\VirtualProperty;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Audio extends DefaultMedia
{
    /**
     * @VirtualProperty
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE_AUDIO;
    }

    /**
     * Get the duration of the audio in seconds, null if unavailable
     *
     * @VirtualProperty
     *
     * @return float
     */
    public function getDuration()
    {
        $sources = array('Composite:Duration');

        if (null !== $value = $this->findInSources($sources)) {
            return (float) $value;
        }

        return null;
    }
}
