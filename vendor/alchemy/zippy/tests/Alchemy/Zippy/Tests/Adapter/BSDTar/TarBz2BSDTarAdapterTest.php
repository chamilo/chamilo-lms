<?php

namespace Alchemy\Zippy\Tests\Adapter\BSDTar;

class TarBz2BSDTarAdapterTest extends BSDTarAdapterWithOptionsTest
{
    protected function getOptions()
    {
        return '--bzip2';
    }

    protected static function getAdapterClassName()
    {
        return 'Alchemy\\Zippy\\Adapter\\BSDTar\\TarBz2BSDTarAdapter';
    }
}
