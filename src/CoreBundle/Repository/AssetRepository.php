<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FilesystemIterator;
use League\Flysystem\FilesystemOperator;
use PhpZip\ZipFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
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

    public function getStorage(): FlysystemStorage
    {
        return $this->storage;
    }

    public function getFileSystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function unZipFile(Asset $asset, string $addFolder = ''): void
    {
        $folder = '/'.$asset->getCategory().'/'.$asset->getTitle();

        if (!empty($addFolder)) {
            $folder .= '/'.$addFolder;
        }

        $fs = $this->getFileSystem();
        $file = $this->getStorage()->resolveUri($asset);

        if (!$fs->fileExists($file)) {
            throw new Exception('file not found');
        }

        $stream = $fs->readStream($file);
        $zipFile = new ZipFile();
        $zipFile->openFromStream($stream);

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

    public function getAssetContent(Asset $asset): string
    {
        if (!$asset->hasFile()) {
            return '';
        }

        $fs = $this->getFileSystem();
        $file = $this->getStorage()->resolveUri($asset);

        if (!$fs->fileExists($file)) {
            return '';
        }

        return $this->getFileSystem()->read($file);
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

    public function createFromRequest(Asset $asset, array $file): Asset
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

    public function createFromString(Asset $asset, string $mimeType, string $content): Asset
    {
        $file = CreateUploadedFileHelper::fromString($asset->getTitle(), $mimeType, $content);
        $asset->setFile($file);
        $this->getEntityManager()->persist($asset);
        $this->getEntityManager()->flush();

        return $asset;
    }

    public function update(Asset $asset): void
    {
        $this->getEntityManager()->persist($asset);
        $this->getEntityManager()->flush();
    }

    /**
     * Deletes an Asset from the database.
     * If it is a SCORM package, first removes its extracted folder on disk.
     */
    public function delete(?Asset $asset = null): void
    {
        if (null === $asset) {
            return;
        }

        // If it is a SCORM package, try to remove its on-disk content (folder or ZIP)
        if (Asset::SCORM === $asset->getCategory()) {
            $path = $this->getFolder($asset); // may be an extracted folder or a .zip file

            if ($path) {
                try {
                    if ($this->filesystem->directoryExists($path)) {
                        $this->filesystem->deleteDirectory($path);
                    } elseif ($this->filesystem->fileExists($path)) {
                        $this->filesystem->delete($path);
                    } else {
                        // Local filesystem fallbacks (log only on true failure)
                        if (@is_dir($path)) {
                            $it = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
                            $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                            foreach ($ri as $file) {
                                $ok = $file->isDir()
                                    ? @rmdir($file->getPathname())
                                    : @unlink($file->getPathname());
                                if (!$ok) {
                                    error_log('[AssetRepository::delete] Failed to remove path: '.$file->getPathname());
                                }
                            }
                            if (!@rmdir($path)) {
                                error_log('[AssetRepository::delete] Failed to remove directory: '.$path);
                            }
                        } elseif (@is_file($path)) {
                            if (!@unlink($path)) {
                                error_log('[AssetRepository::delete] Failed to remove file: '.$path);
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[AssetRepository::delete] Exception while removing SCORM path '.$path.' - '.$e->getMessage());
                }
            }
        }

        // Remove the asset record from the database
        $em = $this->getEntityManager();
        $em->remove($asset);
        $em->flush();
    }

    public function assetFileExists(Asset $asset): bool
    {
        // This checks the physical file existence in the configured filesystem (Flysystem).
        if (!$asset->hasFile()) {
            return false;
        }

        $path = (string) $this->storage->resolveUri($asset);
        if ('' === trim($path)) {
            return false;
        }

        return $this->filesystem->fileExists($path);
    }
}
