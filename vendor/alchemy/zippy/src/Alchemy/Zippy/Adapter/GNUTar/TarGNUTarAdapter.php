<?php

namespace Alchemy\Zippy\Adapter\GNUTar;

use Alchemy\Zippy\Adapter\AbstractTarAdapter;

/**
 * GNUTarAdapter allows you to create and extract files from archives using GNU tar
 *
 * @see http://www.gnu.org/software/tar/manual/tar.html
 */
class TarGNUTarAdapter extends AbstractTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function getName()
    {
        return 'gnu-tar';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultDeflatorBinaryName()
    {
        return array('gnutar', 'tar');
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultInflatorBinaryName()
    {
        return array('gnutar', 'tar');
    }

    /**
     * @inheritdoc
     */
    protected function isProperImplementation($versionOutput)
    {
        $lines = explode("\n", $versionOutput, 2);

        return false !== stripos($lines[0], '(gnu tar)');
    }

    /**
     * {@inheritdoc}
     */
    protected function getListMembersOptions()
    {
        return array('--utc');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractOptions()
    {
        return array('--overwrite-dir', '--overwrite');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractMembersOptions()
    {
        return array('--overwrite-dir', '--overwrite');
    }
}
