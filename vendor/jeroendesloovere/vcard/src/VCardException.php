<?php

namespace JeroenDesloovere\VCard;

/*
 * This file is part of the VCard PHP Class from Jeroen Desloovere.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * VCard Exception PHP Class.
 */
class VCardException extends \Exception
{
    public static function elementAlreadyExists($element)
    {
        return new self('You can only set "' . $element . '" once.');
    }

    public static function emptyURL()
    {
        return new self('Nothing returned from URL.');
    }

    public static function invalidImage()
    {
        return new self('Returned data is not an image.');
    }

    public static function outputDirectoryNotExists()
    {
        return new self('Output directory does not exist.');
    }
}
