<?php

namespace Alchemy\Zippy\Tests\Adapter\GNUTar;

class TarBz2GNUTarAdapterTest extends GNUTarAdapterWithOptionsTest
{
    protected function getOptions()
    {
        return '--bzip2';
    }

    protected static function getAdapterClassName()
    {
        return 'Alchemy\\Zippy\\Adapter\\GNUTar\\TarBz2GNUTarAdapter';
    }
}
