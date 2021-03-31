<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use PhpZip\ZipFile;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class AssetRepository extends ServiceEntityRepository
{
    protected RouterInterface $router;
    protected UploaderHelper $uploaderHelper;
    protected FilesystemOperator $filesystem;

    public function __construct(ManagerRegistry $registry, RouterInterface $router, UploaderHelper $uploaderHelper, FilesystemOperator $assetFilesystem)
    {
        parent::__construct($registry, Asset::class);
        $this->router = $router;
        $this->uploaderHelper = $uploaderHelper;
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml
        $this->filesystem = $assetFilesystem;
    }

    public function getFileSystem()
    {
        return $this->filesystem;
    }

    /*public function getUploaderHelper(): UploaderHelper
    {
        return $this->uploaderHelper;
    }*/

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

    public function getAssetUrl(Asset $asset)
    {
        if (Asset::SCORM === $asset->getCategory()) {
            return $this->router->generate(
                'chamilo_core_asset_showfile',
                [
                    'category' => $asset->getCategory(),
                    'path' => $asset->getTitle(),
                ]
            );
        }

        // Classic.
        $helper = $this->uploaderHelper;

        return '/assets'.$helper->asset($asset);
    }

    /*public function getFileContent(Asset $asset): string
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
    }*/

    /*public function getFileStream(Asset $asset)
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
    }*/

    public function delete(Asset $asset = null): void
    {
        if (null !== $asset) {
            $this->getEntityManager()->remove($asset);
            $this->getEntityManager()->flush();
        }
    }
}
