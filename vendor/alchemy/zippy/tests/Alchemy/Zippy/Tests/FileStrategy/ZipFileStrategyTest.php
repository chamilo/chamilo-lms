<?php

namespace Alchemy\Zippy\Tests\FileStrategy;

use Alchemy\Zippy\FileStrategy\ZipFileStrategy;

class ZipFileStrategyTest extends FileStrategyTestCase
{
    protected function getStrategy($container)
    {
        return new ZipFileStrategy($container);
    }
}
