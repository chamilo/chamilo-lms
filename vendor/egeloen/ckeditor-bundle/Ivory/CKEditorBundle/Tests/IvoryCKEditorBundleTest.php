<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests;

use Ivory\CKEditorBundle\IvoryCKEditorBundle;

/**
 * Ivory CKEditor bundle test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class IvoryCKEditorBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBundle()
    {
        $bundle = new IvoryCKEditorBundle();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
