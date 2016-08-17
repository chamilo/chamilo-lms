<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Utils;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class AudioMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $videoMimeTypes = array(
        'ape' => 'audio/x-monkeys-audio',
        'mp3' => 'audio/mpeg',
    );

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, static::$videoMimeTypes)) {
            return static::$videoMimeTypes[$extension];
        }

        return null;
    }
}
