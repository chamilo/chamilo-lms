<?php

namespace Alchemy\Zippy\Adapter\BSDTar;

class TarGzBSDTarAdapter extends TarBSDTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array('--gzip');
    }
}
