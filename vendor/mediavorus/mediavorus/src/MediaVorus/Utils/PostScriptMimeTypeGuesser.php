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
class PostScriptMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $postscriptMimeTypes = array(
        'eps' => 'application/postscript',
        'ai'  => 'application/illustrator',
    );

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, static::$postscriptMimeTypes)) {
            return static::$postscriptMimeTypes[$extension];
        }

        return null;
    }
}
