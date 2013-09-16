<?php

namespace Alchemy\Zippy\Adapter\GNUTar;

class TarBz2GNUTarAdapter extends TarGNUTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array('--bzip2');
    }
}
