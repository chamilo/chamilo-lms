<?php

namespace JeroenDesloovere\VCard;

// required to load
require_once __DIR__ . '/../vendor/autoload.php';

/*
 * This file is part of the VCard PHP Class from Jeroen Desloovere.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * VCard Exception Test.
 */
class VCardExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException JeroenDesloovere\VCard\VCardException
     */
    public function testException()
    {
        throw new VCardException('Testing the VCard error.');
    }
}
