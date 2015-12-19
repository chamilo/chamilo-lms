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
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Exception
     */
    public function testException()
    {
        throw new Exception('Testing the VCard error.');
    }
}
