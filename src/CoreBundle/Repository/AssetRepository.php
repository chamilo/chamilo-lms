<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class AssetRepository extends ServiceEntityRepository
{
    protected MountManager $mountManager;
    protected RouterInterface $router;
    protected UploaderHelper $uploaderHelper;

    public function __construct(ManagerRegistry $registry, RouterInterface $router, MountManager $mountManager, UploaderHelper $uploaderHelper)
    {
        parent::__construct($registry, Asset::class);
        $this->router = $router;
        $this->mountManager = $mountManager;
        $this->uploaderHelper = $uploaderHelper;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem()
    {
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml
        return $this->mountManager->getFilesystem('assets_fs');
    }

    /*public function getUploaderHelper(): UploaderHelper
    {
        return $this->uploaderHelper;
    }*/

    public function unZipFile(Asset $asset, ZipArchiveAdapter $zipArchiveAdapter): void
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

                $content = (string) $zipArchiveAdapter->read($data['path']);
                $fs->write($folder.'/'.$data['path'], $content);
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

        // Classic

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
