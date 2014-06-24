<?php

namespace Alchemy\Zippy\Tests\Adapter\BSDTar;

class TarGzBSDTarAdapterTest extends BSDTarAdapterWithOptionsTest
{
    protected function getOptions()
    {
        return '--gzip';
    }

    protected static function getAdapterClassName()
    {
        return 'Alchemy\\Zippy\\Adapter\\BSDTar\\TarGzBSDTarAdapter';
    }
}
