<?php

namespace Alchemy\Zippy\Tests\Adapter\GNUTar;

class TarGzGNUTarAdapterTest extends GNUTarAdapterWithOptionsTest
{
    protected function getOptions()
    {
        return '--gzip';
    }

    protected static function getAdapterClassName()
    {
        return 'Alchemy\\Zippy\\Adapter\\GNUTar\\TarGzGNUTarAdapter';
    }
}
