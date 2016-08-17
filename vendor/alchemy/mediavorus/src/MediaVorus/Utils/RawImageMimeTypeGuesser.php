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
class RawImageMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public static $rawMimeTypes = array(
        '3fr' => 'image/x-tika-hasselblad',
        'arw' => 'image/x-tika-sony',
        'bay' => 'image/x-tika-casio',
        'cap' => 'image/x-tika-phaseone',
        'cr2' => 'image/x-tika-canon',
        'crw' => 'image/x-tika-canon',
        'dcs' => 'image/x-tika-kodak',
        'dcr' => 'image/x-tika-kodak',
        'dng' => 'image/x-tika-dng',
        'drf' => 'image/x-tika-kodak',
        'erf' => 'image/x-tika-epson',
        'fff' => 'image/x-tika-imacon',
        'iiq' => 'image/x-tika-phaseone',
        'kdc' => 'image/x-tika-kodak',
        'k25' => 'image/x-tika-kodak',
        'mef' => 'image/x-tika-mamiya',
        'mos' => 'image/x-tika-leaf',
        'mrw' => 'image/x-tika-minolta',
        'nef' => 'image/x-tika-nikon',
        'nrw' => 'image/x-tika-nikon',
        'orf' => 'image/x-tika-olympus',
        'pef' => 'image/x-tika-pentax',
        'ppm' => 'image/x-portable-pixmap',
        'ptx' => 'image/x-tika-pentax',
        'pxn' => 'image/x-tika-logitech',
        'raf' => 'image/x-tika-fuji',
        'raw' => 'image/x-tika-panasonic',
        'r3d' => 'image/x-tika-red',
        'rw2' => 'image/x-tika-panasonic',
        'rwz' => 'image/x-tika-rawzor',
        'sr2' => 'image/x-tika-sony',
        'srf' => 'image/x-tika-sony',
        'x3f' => 'image/x-tika-sigma',
    );

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, static::$rawMimeTypes)) {
            return static::$rawMimeTypes[$extension];
        }

        return null;
    }
}
