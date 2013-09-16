<?php

namespace Alchemy\Zippy\Functional;

use Alchemy\Zippy\Adapter\AdapterInterface;
use Alchemy\Zippy\Adapter\AdapterContainer;
use Symfony\Component\Filesystem\Filesystem;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__ . '/samples/tmp');

        mkdir(__DIR__ . '/samples/tmp');
    }

    /**
     * @return AdapterInterface
     */
    protected function getAdapter()
    {
        if (false === getenv('ZIPPY_ADAPTER')) {
            throw new \RuntimeException('ZIPPY_ADAPTER environment variable is not set');
        }

        $adapter = 'Alchemy\\Zippy\\Adapter\\' . getenv('ZIPPY_ADAPTER');

        if (!class_exists($adapter)) {
            throw new \InvalidArgumentException(sprintf('class %s does not exist', $adapter));
        }

        $container = AdapterContainer::load();
        $adapter = $container[$adapter];

        if (!$adapter->isSupported()) {
            $this->markTestSkipped(sprintf('Adapter %s is not supported', $adapter->getName()));
        }

        return $adapter;
    }

    protected function getArchiveFileForAdapter($adapter)
    {
        switch (get_class($adapter)) {
            case 'Alchemy\Zippy\Adapter\ZipAdapter':
            case 'Alchemy\Zippy\Adapter\ZipExtensionAdapter':
                return __DIR__ . '/samples/archive.zip';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarGzBSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarGzGNUTarAdapter':
                return __DIR__ . '/samples/archive.tar.gz';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarBz2BSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarBz2GNUTarAdapter':
                return __DIR__ . '/samples/archive.tar.bz2';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarBSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter':
                return __DIR__ . '/samples/archive.tar';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unable to find an archive file for %s', get_class($adapter)));
                break;
        }
    }

    protected function getArchiveExtensionForAdapter($adapter)
    {
        switch (get_class($adapter)) {
            case 'Alchemy\Zippy\Adapter\ZipAdapter':
            case 'Alchemy\Zippy\Adapter\ZipExtensionAdapter':
                return 'zip';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarGzBSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarGzGNUTarAdapter':
                return 'tar.gz';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarBz2BSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarBz2GNUTarAdapter':
                return 'tar.bz2';
                break;
            case 'Alchemy\Zippy\Adapter\BSDTar\TarBSDTarAdapter':
            case 'Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter':
                return 'tar';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unable to find an archive file for %s', get_class($adapter)));
                break;
        }
    }
}
