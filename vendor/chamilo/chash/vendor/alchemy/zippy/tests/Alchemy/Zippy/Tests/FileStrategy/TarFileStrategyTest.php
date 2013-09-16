<?php

namespace Alchemy\Zippy\Tests\FileStrategy;

use Alchemy\Zippy\FileStrategy\TarFileStrategy;

class TarFileStrategyTest extends FileStrategyTestCase
{
    protected function getStrategy($container)
    {
        return new TarFileStrategy($container);
    }
}
