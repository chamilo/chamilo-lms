<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Helper;

use Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper;

/**
 * Assets version trimer helper test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class AssetsVersionTrimerHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper */
    protected $assetsVersionTrimerHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->assetsVersionTrimerHelper = new AssetsVersionTrimerHelper();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->assetsVersionTrimerHelper);
    }

    public function testTrimAssetVersionWithVersion()
    {
        $this->assertSame('/bar', $this->assetsVersionTrimerHelper->trim('/bar?v2'));
    }

    public function testTrimAssetVersionWithoutVersion()
    {
        $this->assertSame('/bar', $this->assetsVersionTrimerHelper->trim('/bar'));
    }
}
