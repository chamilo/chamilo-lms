<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Vich\UploaderBundle\Storage\FlysystemStorage;

class AssetRepository extends ServiceEntityRepository
{
    protected $mountManager;
    protected $storage;

    public function __construct(ManagerRegistry $registry, FlysystemStorage $storage, MountManager $mountManager)
    {
        parent::__construct($registry, Asset::class);
        $this->storage = $storage;
        $this->mountManager = $mountManager;
    }

    public function getFilename(ResourceFile $resourceFile)
    {
        return $this->storage->resolveUri($resourceFile);
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem()
    {
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml
        return $this->mountManager->getFilesystem('assets_fs');
    }

    public function unZipFile(Asset $asset, ZipArchiveAdapter $zipArchiveAdapter)
    {
        $folder = '/'.$asset->getCategory().'/'.$asset->getTitle();

        $fs = $this->getFileSystem();
        if ($fs->has($folder)) {
            $contents = $zipArchiveAdapter->listContents();
            foreach ($contents as $data) {
                if ($fs->has($folder.'/'.$data['path'])) {
                    continue;
                }

                if ('dir' === $data['type']) {
                    $fs->createDir($folder.'/'.$data['path']);
                    continue;
                }

                $fs->write($folder.'/'.$data['path'], $zipArchiveAdapter->read($data['path']));
            }
        }
    }

    public function getFileContent(Asset $asset): string
    {
        try {
            if ($asset->hasFile()) {
                $file = $asset->getFile();
                $fileName = $this->getFilename($file);

                return $this->getFileSystem()->read($fileName);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($asset);
        }
    }

    public function getFileStream(Asset $asset)
    {
        try {
            if ($asset->hasFile()) {
                $file = $asset->getFile();
                $fileName = $this->getFilename($file);

                return $this->getFileSystem()->readStream($fileName);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($asset);
        }
    }
}
