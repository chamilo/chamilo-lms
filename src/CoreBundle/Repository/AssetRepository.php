<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Storage\FlysystemStorage;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class AssetRepository extends ServiceEntityRepository
{
    protected RouterInterface $router;
    protected UploaderHelper $uploaderHelper;
    protected FilesystemOperator $filesystem;
    protected FlysystemStorage $storage;

    public function __construct(ManagerRegistry $registry, RouterInterface $router, UploaderHelper $uploaderHelper, FilesystemOperator $assetFilesystem, FlysystemStorage $storage)
    {
        parent::__construct($registry, Asset::class);
        $this->router = $router;
        $this->uploaderHelper = $uploaderHelper;
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml
        $this->filesystem = $assetFilesystem;
        $this->storage = $storage;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function getFileSystem()
    {
        return $this->filesystem;
    }

    public function unZipFile(Asset $asset, ZipFile $zipFile): void
    {
        $folder = '/'.$asset->getCategory().'/'.$asset->getTitle();

        $fs = $this->getFileSystem();

        if ($fs->fileExists($folder)) {
            $list = $zipFile->getEntries();
            foreach ($list as $item) {
                $name = $item->getName();
                if ($fs->fileExists($folder.'/'.$name)) {
                    continue;
                }

                if ($item->isDirectory()) {
                    $fs->createDirectory($folder.'/'.$name);

                    continue;
                }

                $content = $zipFile->getEntryContents($name);
                $fs->write($folder.'/'.$name, $content);
            }
        }
    }

    public function getFolder(Asset $asset): ?string
    {
        if ($asset->hasFile()) {
            $file = $asset->getTitle();

            return '/'.$asset->getCategory().'/'.$file.'/';
        }

        return null;
    }

    public function getAssetUrl(Asset $asset): string
    {
        if (Asset::SCORM === $asset->getCategory()) {
            $params = [
                'category' => $asset->getCategory(),
                'path' => $asset->getTitle(),
            ];

            return $this->router->generate('chamilo_core_asset_showfile', $params);
        }

        // Classic.
        $helper = $this->uploaderHelper;

        $cropFilter = '';
        $crop = $asset->getCrop();
        if (!empty($crop)) {
            $cropFilter = '?crop='.$crop;
        }

        return '/assets'.$helper->asset($asset).$cropFilter;
    }

    public function createFromRequest(Asset $asset, $file): Asset
    {
        if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
            $mimeType = mime_content_type($file['tmp_name']);
            $file = new UploadedFile($file['tmp_name'], $asset->getTitle(), $mimeType, null, true);
            $asset->setFile($file);
            $this->getEntityManager()->persist($asset);
            $this->getEntityManager()->flush();
        }

        return $asset;
    }

    public function delete(Asset $asset = null): void
    {
        if (null !== $asset) {
            $this->getEntityManager()->remove($asset);
            $this->getEntityManager()->flush();
        }
    }
}
