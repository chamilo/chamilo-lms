<?php

namespace Alchemy\Zippy\Adapter\GNUTar;

class TarGzGNUTarAdapter extends TarGNUTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array('--gzip');
    }
}
