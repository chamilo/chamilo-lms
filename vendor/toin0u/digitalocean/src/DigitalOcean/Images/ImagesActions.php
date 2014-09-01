<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Images;

/**
 * ImagesActions class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ImagesActions
{
    /**
     * Available actions.
     *
     * @var string
     */
    const ACTION_FILTER_MY_IMAGES = 'my_images';
    const ACTION_FILTER_GLOBAL    = 'global';
    const ACTION_DESTROY_IMAGE    = 'destroy';
    const ACTION_TRANSFERT        = 'transfert';
}
