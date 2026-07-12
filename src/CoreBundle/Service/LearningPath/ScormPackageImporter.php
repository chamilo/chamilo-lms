<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LearningPathCreatedEvent;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use ZipArchive;

final readonly class ScormPackageImporter
{
    private const MAX_ARCHIVE_ENTRIES = 20000;
    private const MAX_MANIFEST_SIZE = 10485760;
    private const MAX_UNCOMPRESSED_SIZE = 2147483648;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AssetRepository $assetRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private CDocumentRepository $documentRepository,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private ScormManifestParser $manifestParser,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array<int, array{id: int, title: string}>
     */
    public function import(
        UploadedFile $package,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $useMaxScore,
        string $contentProximity,
        string $contentMaker,
        bool $allowHtaccess,
    ): array {
        $packagePath = $package->getPathname();
        $originalName = trim($package->getClientOriginalName());
        if (!$package->isValid() || '' === $packagePath || !is_file($packagePath)) {
            throw new RuntimeException('The uploaded package is not valid.');
        }
        if ('zip' !== strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION))) {
            throw new RuntimeException('Only ZIP packages can be imported.');
        }

        $inspection = $this->inspectArchive($packagePath);
        $manifest = $this->manifestParser->parse($inspection['manifestXml']);
        $this->validateManifestResources(
            $manifest,
            $inspection['entries'],
            $inspection['manifestDirectory'],
            $contentProximity,
        );

        $backupPath = tempnam(sys_get_temp_dir(), 'chamilo_scorm_');
        if (false === $backupPath || !copy($packagePath, $backupPath)) {
            throw new RuntimeException('The uploaded package could not be prepared for import.');
        }

        $connection = $this->entityManager->getConnection();
        $assets = [];
        $created = [];
        $connection->beginTransaction();

        try {
            foreach ($manifest['organizations'] as $organizationIndex => $organization) {
                $asset = $this->createAsset($backupPath, $originalName);
                $assets[] = $asset;

                $packageRoot = $this->buildPackageRoot($originalName, $organizationIndex, (string) $asset->getId());
                $this->extractArchive(
                    $backupPath,
                    $asset,
                    $packageRoot,
                    $inspection['entries'],
                    $allowHtaccess,
                );

                $lp = $this->createLearningPath(
                    $course,
                    $session,
                    $group,
                    $asset,
                    $organization,
                    $manifest['resources'],
                    $manifest['version'],
                    $manifest['encoding'],
                    $packageRoot,
                    $inspection['manifestDirectory'],
                    $useMaxScore,
                    $contentProximity,
                    $contentMaker,
                );

                $archiveCopy = $this->createUploadedCopy($backupPath, $originalName);
                try {
                    $this->documentRepository->registerScormZip($course, $session, $lp, $archiveCopy, $group);
                } finally {
                    $copyPath = $archiveCopy->getPathname();
                    if (is_file($copyPath)) {
                        @unlink($copyPath);
                    }
                }

                $created[] = [
                    'id' => (int) $lp->getIid(),
                    'title' => $lp->getTitle(),
                ];
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $filesystem = $this->assetRepository->getFileSystem();
            foreach ($assets as $asset) {
                try {
                    $folder = (string) $this->assetRepository->getFolder($asset);
                    if ('' !== $folder && $filesystem->directoryExists($folder)) {
                        $filesystem->deleteDirectory($folder);
                    }
                } catch (Throwable) {
                    // Keep the original import exception as the actionable failure.
                }
            }

            $this->entityManager->clear();

            throw $exception;
        } finally {
            if (is_file($backupPath)) {
                @unlink($backupPath);
            }
        }

        return $created;
    }

    public function update(
        UploadedFile $package,
        CLp $learningPath,
        Course $course,
        bool $allowHtaccess,
    ): void {
        if (CLp::SCORM_TYPE !== $learningPath->getLpType()) {
            throw new RuntimeException('Only SCORM learning paths can be updated.');
        }

        $asset = $learningPath->getAsset();
        if (!$asset instanceof Asset) {
            throw new RuntimeException('The SCORM learning path does not have an updateable package asset.');
        }

        $packagePath = $package->getPathname();
        $originalName = trim($package->getClientOriginalName());
        if (!$package->isValid() || '' === $packagePath || !is_file($packagePath)) {
            throw new RuntimeException('The uploaded package is not valid.');
        }
        if ('zip' !== strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION))) {
            throw new RuntimeException('Only ZIP packages can be imported.');
        }

        $expectedName = trim($asset->getOriginalName());
        if ('' === $expectedName) {
            $expectedName = trim((string) $asset->getTitle());
        }
        if ('' === $expectedName
            || $this->normalizePackageBaseName($expectedName) !== $this->normalizePackageBaseName($originalName)
        ) {
            throw new RuntimeException(
                'The uploaded ZIP file name must match the original SCORM package name.',
            );
        }

        $inspection = $this->inspectArchive($packagePath);
        $manifest = $this->manifestParser->parse($inspection['manifestXml']);
        $this->validateManifestResources(
            $manifest,
            $inspection['entries'],
            $inspection['manifestDirectory'],
            $learningPath->getContentLocal(),
        );

        $organization = $this->selectUpdateOrganization($manifest, $learningPath);
        $this->assertUpdateVersion($manifest, $learningPath);
        $this->assertUpdateStructure($learningPath, $organization, $manifest['resources']);

        $pathParts = array_values(array_filter(
            explode('/', trim($learningPath->getPath(), '/')),
            static fn (string $part): bool => '' !== $part,
        ));
        $packageRoot = (string) ($pathParts[0] ?? '');
        if ('' === $packageRoot) {
            throw new RuntimeException('The existing SCORM package folder cannot be resolved.');
        }

        $baseFolder = rtrim((string) $this->assetRepository->getFolder($asset), '/');
        if ('' === $baseFolder) {
            throw new RuntimeException('The SCORM asset storage folder could not be resolved.');
        }

        $filesystem = $this->assetRepository->getFileSystem();
        $assetUri = (string) $this->assetRepository->getStorage()->resolveUri($asset);
        if ('' === $assetUri) {
            throw new RuntimeException('The original SCORM ZIP package cannot be found.');
        }
        $assetArchiveExisted = $filesystem->fileExists($assetUri);

        $registeredDocument = $this->documentRepository->findScormZipDocument($course, $learningPath);
        $registeredResourceFile = null;
        $registeredArchiveUri = '';
        $registeredFilesystem = $this->resourceNodeRepository->getFileSystem();

        if ($registeredDocument instanceof CDocument) {
            $registeredNode = $registeredDocument->getResourceNode();
            $candidate = $registeredNode?->getFirstResourceFile();
            if ($candidate instanceof ResourceFile) {
                $candidateUri = (string) $this->resourceNodeRepository->getFilename($candidate);
                if ('' !== $candidateUri) {
                    $registeredResourceFile = $candidate;
                    $registeredArchiveUri = $candidateUri;
                }
            }
        }

        $registeredArchiveExisted = '' !== $registeredArchiveUri
            && $registeredFilesystem->fileExists($registeredArchiveUri);
        if (!$assetArchiveExisted && !$registeredArchiveExisted) {
            throw new RuntimeException('The original SCORM ZIP package cannot be found.');
        }

        $lockHandle = $this->acquireUpdateLock((int) $learningPath->getIid());
        $zipBackupPath = tempnam(sys_get_temp_dir(), 'chamilo_scorm_update_');
        if (false === $zipBackupPath) {
            $this->releaseUpdateLock($lockHandle);
            throw new RuntimeException('The original SCORM ZIP package could not be backed up.');
        }

        $suffix = bin2hex(random_bytes(6));
        $stagingRoot = $packageRoot.'__tmp_'.$suffix;
        $backupRoot = $packageRoot.'__bak_'.$suffix;
        $stagingPath = $baseFolder.'/'.$stagingRoot;
        $targetPath = $baseFolder.'/'.$packageRoot;
        $backupPath = $baseFolder.'/'.$backupRoot;
        $newLearningPathPath = $packageRoot.(
            '' !== $inspection['manifestDirectory'] ? '/'.$inspection['manifestDirectory'] : ''
        );

        $connection = $this->entityManager->getConnection();
        $targetMoved = false;
        $targetPromoted = false;
        $packageReplaced = false;
        $registeredPackageReplaced = false;
        $registeredBackupPath = null;

        try {
            if ($assetArchiveExisted) {
                $this->copyFilesystemFileToLocal($filesystem, $assetUri, $zipBackupPath);
            }

            if ($registeredArchiveExisted) {
                $registeredBackupPath = tempnam(sys_get_temp_dir(), 'chamilo_scorm_document_');
                if (false === $registeredBackupPath) {
                    throw new RuntimeException('The registered SCORM ZIP document could not be backed up.');
                }

                $this->copyFilesystemFileToLocal(
                    $registeredFilesystem,
                    $registeredArchiveUri,
                    $registeredBackupPath,
                );
                $registeredArchiveExisted = true;
            }

            $connection->beginTransaction();

            $this->extractArchive(
                $packagePath,
                $asset,
                $stagingRoot,
                $inspection['entries'],
                $allowHtaccess,
            );

            $manifestPath = $stagingPath.(
                '' !== $inspection['manifestDirectory'] ? '/'.$inspection['manifestDirectory'] : ''
            ).'/imsmanifest.xml';
            if (!$filesystem->fileExists($manifestPath)) {
                throw new RuntimeException('The updated SCORM package manifest could not be extracted.');
            }

            if ($filesystem->directoryExists($backupPath)) {
                $filesystem->deleteDirectory($backupPath);
            }
            if ($filesystem->directoryExists($targetPath)) {
                $filesystem->move($targetPath, $backupPath);
                $targetMoved = true;
            }

            $filesystem->move($stagingPath, $targetPath);
            $targetPromoted = true;
            $packageReplaced = true;
            $this->writeLocalFileToFilesystem($filesystem, $packagePath, $assetUri);

            if ('' !== $registeredArchiveUri && $registeredResourceFile instanceof ResourceFile) {
                $registeredPackageReplaced = true;
                $this->writeLocalFileToFilesystem(
                    $registeredFilesystem,
                    $packagePath,
                    $registeredArchiveUri,
                );

                $registeredResourceFile
                    ->setOriginalName($originalName)
                    ->setMimeType('application/zip')
                    ->setSize((int) filesize($packagePath))
                ;

                if ($registeredDocument instanceof CDocument) {
                    $registeredDocument->setTitle($originalName);
                    $registeredNode = $registeredDocument->getResourceNode();
                    $registeredNode?->setTitle($originalName);
                    $registeredNode?->setUpdatedAt(new DateTime());

                    $this->entityManager->persist($registeredDocument);
                    if (null !== $registeredNode) {
                        $this->entityManager->persist($registeredNode);
                    }
                }

                $this->entityManager->persist($registeredResourceFile);
            }

            $asset
                ->setCompressed(true)
                ->setMimeType('application/zip')
                ->setSize((int) filesize($packagePath))
            ;
            $learningPath
                ->setPath($newLearningPathPath)
                ->setDefaultEncoding(
                    '' !== trim((string) $manifest['encoding'])
                        ? (string) $manifest['encoding']
                        : 'UTF-8',
                )
                ->setModifiedOn(new DateTime())
            ;

            $this->entityManager->persist($asset);
            $this->entityManager->persist($learningPath);
            $this->entityManager->flush();
            $connection->commit();

            try {
                if ($filesystem->directoryExists($backupPath)) {
                    $filesystem->deleteDirectory($backupPath);
                }
            } catch (Throwable) {
                // The update is already committed; stale backup cleanup is non-blocking.
            }
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            try {
                if (($targetMoved || $targetPromoted) && $filesystem->directoryExists($targetPath)) {
                    $filesystem->deleteDirectory($targetPath);
                }
                if ($targetMoved && $filesystem->directoryExists($backupPath)) {
                    $filesystem->move($backupPath, $targetPath);
                }
                if ($filesystem->directoryExists($stagingPath)) {
                    $filesystem->deleteDirectory($stagingPath);
                }
                if ($packageReplaced) {
                    if ($assetArchiveExisted && is_file($zipBackupPath)) {
                        $this->writeLocalFileToFilesystem($filesystem, $zipBackupPath, $assetUri);
                    } elseif (!$assetArchiveExisted && $filesystem->fileExists($assetUri)) {
                        $filesystem->delete($assetUri);
                    }
                }
                if ($registeredPackageReplaced && '' !== $registeredArchiveUri) {
                    if ($registeredArchiveExisted
                        && \is_string($registeredBackupPath)
                        && is_file($registeredBackupPath)
                    ) {
                        $this->writeLocalFileToFilesystem(
                            $registeredFilesystem,
                            $registeredBackupPath,
                            $registeredArchiveUri,
                        );
                    } elseif ($registeredFilesystem->fileExists($registeredArchiveUri)) {
                        $registeredFilesystem->delete($registeredArchiveUri);
                    }
                }
            } catch (Throwable) {
                // Preserve the original update exception.
            }

            throw $exception;
        } finally {
            if (is_file($zipBackupPath)) {
                @unlink($zipBackupPath);
            }
            if (\is_string($registeredBackupPath) && is_file($registeredBackupPath)) {
                @unlink($registeredBackupPath);
            }
            $this->releaseUpdateLock($lockHandle);
        }
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @return array{identifier: string, title: string, items: array<int, array<string, mixed>>}
     */
    private function selectUpdateOrganization(array $manifest, CLp $learningPath): array
    {
        $learningPathReference = trim((string) $learningPath->getRef());

        foreach ($manifest['organizations'] as $organization) {
            if ('' !== $learningPathReference
                && $learningPathReference === trim((string) ($organization['identifier'] ?? ''))
            ) {
                return $organization;
            }
        }

        if ('' !== $learningPathReference) {
            throw new RuntimeException(
                'The uploaded SCORM package does not contain the original organization.',
            );
        }

        if (1 === \count($manifest['organizations'])) {
            return $manifest['organizations'][0];
        }

        throw new RuntimeException(
            'The uploaded SCORM package does not contain the original organization.',
        );
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function assertUpdateVersion(array $manifest, CLp $learningPath): void
    {
        $currentVersion = str_contains(strtolower($learningPath->getJsLib()), '2004')
            ? ScormRuntimeManager::VERSION_2004
            : ScormRuntimeManager::VERSION_12;

        if ($currentVersion !== (string) ($manifest['version'] ?? '')) {
            throw new RuntimeException(
                'The updated package must use the same SCORM version as the current learning path.',
            );
        }
    }

    /**
     * @param array{identifier: string, title: string, items: array<int, array<string, mixed>>} $organization
     * @param array<string, array{href: string, scormType: string}>                              $resources
     */
    private function assertUpdateStructure(CLp $learningPath, array $organization, array $resources): void
    {
        $manifestItems = [];
        $this->flattenManifestItems($organization['items'], $resources, '', $manifestItems);

        $existingItems = [];
        foreach ($this->lpItemRepository->findBy(['lp' => $learningPath], ['displayOrder' => 'ASC']) as $item) {
            if (!$item instanceof CLpItem || 'root' === $item->getItemType()) {
                continue;
            }

            $reference = trim($item->getRef());
            if ('' === $reference || isset($existingItems[$reference])) {
                throw new RuntimeException(
                    'The current SCORM learning path does not have a safely updateable item structure.',
                );
            }

            $parent = $item->getParent();
            $parentReference = '';
            if ($parent instanceof CLpItem && 'root' !== $parent->getItemType()) {
                $parentReference = trim($parent->getRef());
            }

            $existingItems[$reference] = [
                'path' => trim((string) $item->getPath()),
                'type' => $item->getItemType(),
                'parent' => $parentReference,
            ];
        }

        if (\count($existingItems) !== \count($manifestItems)) {
            throw new RuntimeException(
                'The uploaded SCORM manifest changes the learning path structure. Import it as a new package instead.',
            );
        }

        foreach ($existingItems as $reference => $existingItem) {
            $manifestItem = $manifestItems[$reference] ?? null;
            if (!\is_array($manifestItem)
                || $existingItem['path'] !== $manifestItem['path']
                || $existingItem['type'] !== $manifestItem['type']
                || $existingItem['parent'] !== $manifestItem['parent']
            ) {
                throw new RuntimeException(
                    'The uploaded SCORM manifest changes the learning path structure. Import it as a new package instead.',
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>>                      $items
     * @param array<string, array{href: string, scormType: string}> $resources
     * @param array<string, array{path: string, type: string, parent: string}> $result
     */
    private function flattenManifestItems(
        array $items,
        array $resources,
        string $parentReference,
        array &$result,
    ): void {
        foreach ($items as $item) {
            $reference = trim((string) ($item['identifier'] ?? ''));
            if ('' === $reference || isset($result[$reference])) {
                throw new RuntimeException('The uploaded SCORM manifest contains invalid item identifiers.');
            }

            $identifierReference = trim((string) ($item['identifierRef'] ?? ''));
            $resource = $resources[$identifierReference] ?? null;
            $path = \is_array($resource) ? trim((string) ($resource['href'] ?? '')) : '';
            $type = '' === $path ? 'dir' : (string) ($resource['scormType'] ?? 'sco');

            $result[$reference] = [
                'path' => $path,
                'type' => $type,
                'parent' => $parentReference,
            ];

            $children = $item['children'] ?? [];
            if (\is_array($children) && [] !== $children) {
                $this->flattenManifestItems($children, $resources, $reference, $result);
            }
        }
    }

    private function normalizePackageBaseName(string $name): string
    {
        $baseName = strtolower((string) pathinfo(basename($name), PATHINFO_FILENAME));

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $baseName), '-');
    }

    /**
     * @return resource
     */
    private function acquireUpdateLock(int $learningPathId): mixed
    {
        $handle = fopen(sys_get_temp_dir().'/chamilo_scorm_update_'.$learningPathId.'.lock', 'c+');
        if (!\is_resource($handle) || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (\is_resource($handle)) {
                fclose($handle);
            }

            throw new RuntimeException('The SCORM package is already being updated.');
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private function releaseUpdateLock(mixed $handle): void
    {
        if (!\is_resource($handle)) {
            return;
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function copyFilesystemFileToLocal(
        FilesystemOperator $filesystem,
        string $source,
        string $destination,
    ): void {
        $sourceStream = $filesystem->readStream($source);
        $destinationStream = fopen($destination, 'wb');
        if (!\is_resource($sourceStream) || !\is_resource($destinationStream)) {
            if (\is_resource($sourceStream)) {
                fclose($sourceStream);
            }
            if (\is_resource($destinationStream)) {
                fclose($destinationStream);
            }

            throw new RuntimeException('The original SCORM ZIP package could not be backed up.');
        }

        try {
            if (false === stream_copy_to_stream($sourceStream, $destinationStream)) {
                throw new RuntimeException('The original SCORM ZIP package could not be backed up.');
            }
        } finally {
            fclose($sourceStream);
            fclose($destinationStream);
        }
    }

    private function writeLocalFileToFilesystem(
        FilesystemOperator $filesystem,
        string $source,
        string $destination,
    ): void {
        $stream = fopen($source, 'rb');
        if (!\is_resource($stream)) {
            throw new RuntimeException('The SCORM ZIP package could not be read.');
        }

        try {
            $filesystem->writeStream($destination, $stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return array{
     *     manifestXml: string,
     *     manifestPath: string,
     *     manifestDirectory: string,
     *     entries: array<int, array{name: string, archiveName: string, directory: bool}>
     * }
     */
    private function inspectArchive(string $packagePath): array
    {
        $archive = new ZipArchive();
        if (true !== $archive->open($packagePath)) {
            throw new RuntimeException('The uploaded ZIP package cannot be opened.');
        }

        $entries = [];
        $manifestCandidates = [];
        $seenEntries = [];
        $totalSize = 0;

        try {
            if ($archive->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new RuntimeException('The SCORM package contains too many files.');
            }

            for ($index = 0; $index < $archive->numFiles; ++$index) {
                $stat = $archive->statIndex($index);
                if (!\is_array($stat)) {
                    throw new RuntimeException('The SCORM package contains an unreadable entry.');
                }

                $rawName = (string) ($stat['name'] ?? '');
                $name = $this->normalizeArchiveEntry($rawName);
                if ('' === $name) {
                    continue;
                }

                if (isset($seenEntries[$name])) {
                    throw new RuntimeException('The SCORM package contains duplicate normalized paths.');
                }
                $seenEntries[$name] = true;

                $this->assertAllowedEntry($archive, $index, $name);
                $directory = str_ends_with($rawName, '/');
                $entries[] = ['name' => $name, 'archiveName' => $rawName, 'directory' => $directory];
                $totalSize += (int) ($stat['size'] ?? 0);
                if ($totalSize > self::MAX_UNCOMPRESSED_SIZE) {
                    throw new RuntimeException('The uncompressed SCORM package is too large.');
                }

                if (!$directory && 'imsmanifest.xml' === strtolower((string) basename($name))) {
                    $manifestCandidates[] = ['name' => $name, 'archiveName' => $rawName];
                }
            }

            if ([] === $manifestCandidates) {
                throw new RuntimeException('The package does not contain imsmanifest.xml.');
            }

            usort(
                $manifestCandidates,
                static fn (array $left, array $right): int =>
                    substr_count($left['name'], '/') <=> substr_count($right['name'], '/'),
            );
            $manifestPath = $manifestCandidates[0]['name'];
            $manifestXml = $archive->getFromName($manifestCandidates[0]['archiveName']);
            if (false === $manifestXml || '' === trim($manifestXml)) {
                throw new RuntimeException('The SCORM manifest could not be read.');
            }
            if (strlen($manifestXml) > self::MAX_MANIFEST_SIZE) {
                throw new RuntimeException('The SCORM manifest is too large.');
            }

            $manifestDirectory = dirname($manifestPath);
            if ('.' === $manifestDirectory) {
                $manifestDirectory = '';
            }

            return [
                'manifestXml' => $manifestXml,
                'manifestPath' => $manifestPath,
                'manifestDirectory' => trim(str_replace('\\', '/', $manifestDirectory), '/'),
                'entries' => $entries,
            ];
        } finally {
            $archive->close();
        }
    }

    private function assertAllowedEntry(ZipArchive $archive, int $index, string $name): void
    {
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (\in_array($extension, ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar'], true)) {
            throw new RuntimeException('The SCORM package contains an executable server-side script.');
        }
        $operations = 0;
        $attributes = 0;
        if ($archive->getExternalAttributesIndex($index, $operations, $attributes)) {
            $unixMode = ($attributes >> 16) & 0170000;
            if (0120000 === $unixMode) {
                throw new RuntimeException('The SCORM package contains a symbolic link.');
            }
        }
    }

    private function normalizeArchiveEntry(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        if ('' === $name) {
            return '';
        }
        if (str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:/#', $name)) {
            throw new RuntimeException('The SCORM package contains an unsafe path.');
        }

        $segments = [];
        foreach (explode('/', $name) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                throw new RuntimeException('The SCORM package contains an unsafe path.');
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    /**
     * @param array<string, mixed>                                                    $manifest
     * @param array<int, array{name: string, archiveName: string, directory: bool}> $entries
     */
    private function validateManifestResources(
        array $manifest,
        array $entries,
        string $manifestDirectory,
        string $contentProximity,
    ): void
    {
        $entryMap = [];
        foreach ($entries as $entry) {
            if (!$entry['directory']) {
                $entryMap[$entry['name']] = true;
            }
        }

        foreach ($manifest['resources'] as $resource) {
            $href = (string) ($resource['href'] ?? '');
            if ('' === $href) {
                continue;
            }
            if ($this->isRemoteResource($href)) {
                if ('remote' !== $contentProximity) {
                    throw new RuntimeException(
                        'The SCORM manifest contains a remote resource but the package is configured as local.',
                    );
                }

                continue;
            }

            $path = '' !== $manifestDirectory ? $manifestDirectory.'/'.$href : $href;
            $path = $this->normalizeArchiveEntry($path);
            if (!isset($entryMap[$path])) {
                throw new RuntimeException(
                    'A resource declared in imsmanifest.xml is missing from the package: '.$href,
                );
            }
        }
    }

    private function isRemoteResource(string $href): bool
    {
        return 1 === preg_match('#^https?://#i', $href);
    }

    private function createAsset(string $sourcePath, string $originalName): Asset
    {
        $originalName = mb_substr(basename($originalName), 0, 255);
        $upload = $this->createUploadedCopy($sourcePath, $originalName);
        $asset = (new Asset())
            ->setCategory(Asset::SCORM)
            ->setTitle($originalName)
            ->setCompressed(true)
            ->setFile($upload)
        ;
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        return $asset;
    }

    private function createUploadedCopy(string $sourcePath, string $originalName): UploadedFile
    {
        $copy = tempnam(sys_get_temp_dir(), 'chamilo_scorm_copy_');
        if (false === $copy || !copy($sourcePath, $copy)) {
            throw new RuntimeException('The SCORM package could not be copied.');
        }

        return new UploadedFile($copy, $originalName, 'application/zip', null, true);
    }

    /**
     * @param array<int, array{name: string, archiveName: string, directory: bool}> $entries
     */
    private function extractArchive(
        string $packagePath,
        Asset $asset,
        string $packageRoot,
        array $entries,
        bool $allowHtaccess,
    ): void {
        $baseFolder = rtrim((string) $this->assetRepository->getFolder($asset), '/');
        if ('' === $baseFolder) {
            throw new RuntimeException('The SCORM asset storage folder could not be resolved.');
        }

        $destination = $baseFolder.'/'.$packageRoot;
        $filesystem = $this->assetRepository->getFileSystem();
        if ($filesystem->directoryExists($destination)) {
            $filesystem->deleteDirectory($destination);
        }
        $filesystem->createDirectory($destination);

        $archive = new ZipArchive();
        if (true !== $archive->open($packagePath)) {
            throw new RuntimeException('The SCORM package could not be reopened for extraction.');
        }

        try {
            foreach ($entries as $entry) {
                $name = $entry['name'];
                if (!$allowHtaccess && '.htaccess' === strtolower((string) basename($name))) {
                    continue;
                }

                $target = $destination.'/'.$name;
                if ($entry['directory']) {
                    if (!$filesystem->directoryExists($target)) {
                        $filesystem->createDirectory($target);
                    }
                    continue;
                }

                $parent = dirname($target);
                if (!$filesystem->directoryExists($parent)) {
                    $filesystem->createDirectory($parent);
                }

                $stream = $archive->getStream($entry['archiveName']);
                if (!\is_resource($stream)) {
                    throw new RuntimeException('A SCORM package file could not be extracted: '.$name);
                }

                try {
                    $filesystem->writeStream($target, $stream);
                } finally {
                    fclose($stream);
                }
            }
        } catch (Throwable $exception) {
            if ($filesystem->directoryExists($destination)) {
                $filesystem->deleteDirectory($destination);
            }
            throw $exception;
        } finally {
            $archive->close();
        }
    }

    /**
     * @param array{identifier: string, title: string, items: array<int, array<string, mixed>>} $organization
     * @param array<string, array{href: string, scormType: string}>                              $resources
     */
    private function createLearningPath(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Asset $asset,
        array $organization,
        array $resources,
        string $version,
        string $encoding,
        string $packageRoot,
        string $manifestDirectory,
        bool $useMaxScore,
        string $contentProximity,
        string $contentMaker,
    ): CLp {
        $courseNode = $course->getResourceNode();
        if (null === $courseNode) {
            throw new RuntimeException('The course resource node is missing.');
        }

        $link = [
            'cid' => (int) $course->getId(),
            'visibility' => ResourceLink::VISIBILITY_DRAFT,
        ];
        if (null !== $session) {
            $link['sid'] = (int) $session->getId();
        }
        if (null !== $group) {
            $link['gid'] = (int) $group->getIid();
        }

        $lpPath = $packageRoot.('' !== $manifestDirectory ? '/'.$manifestDirectory : '');
        $lp = (new CLp())
            ->setLpType(CLp::SCORM_TYPE)
            ->setTitle(mb_substr(trim((string) $organization['title']) ?: 'Untitled', 0, 255))
            ->setRef((string) $organization['identifier'])
            ->setPath($lpPath)
            ->setDefaultEncoding('' !== trim($encoding) ? $encoding : 'UTF-8')
            ->setJsLib(ScormRuntimeManager::VERSION_2004 === $version ? 'scorm_2004' : 'scorm_1_2')
            ->setUseMaxScore($useMaxScore ? 1 : 0)
            ->setAsset($asset)
            ->setContentLocal($contentProximity)
            ->setContentMaker($contentMaker)
            ->setParentResourceNode((int) $courseNode->getId())
            ->setResourceLinkArray([$link])
        ;

        $this->lpRepository->createLp($lp);
        $root = $this->lpItemRepository->getRootItem((int) $lp->getIid());
        if (!$root instanceof CLpItem) {
            throw new RuntimeException('The SCORM learning path root item could not be created.');
        }

        $order = 0;
        $this->createItems(
            $lp,
            $root,
            $root,
            $organization['items'],
            $resources,
            $useMaxScore,
            $order,
        );
        $this->entityManager->flush();
        $this->lpItemRepository->recoverNode($root, 'displayOrder');
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new LearningPathCreatedEvent(['lp' => $lp]),
            Events::LP_CREATED,
        );

        return $lp;
    }

    /**
     * @param array<int, array<string, mixed>>                         $manifestItems
     * @param array<string, array{href: string, scormType: string}>    $resources
     */
    private function createItems(
        CLp $lp,
        CLpItem $root,
        CLpItem $parent,
        array $manifestItems,
        array $resources,
        bool $useMaxScore,
        int &$order,
    ): void {
        foreach ($manifestItems as $manifestItem) {
            ++$order;
            $identifierRef = (string) ($manifestItem['identifierRef'] ?? '');
            $resource = $resources[$identifierRef] ?? null;
            $href = \is_array($resource) ? (string) ($resource['href'] ?? '') : '';
            $itemType = '' === $href ? 'dir' : (string) ($resource['scormType'] ?? 'sco');

            $identifier = trim((string) ($manifestItem['identifier'] ?? ''));
            if ('' === $identifier) {
                $identifier = 'item_'.$order;
            }
            $title = trim((string) ($manifestItem['title'] ?? 'Untitled')) ?: 'Untitled';

            $item = (new CLpItem())
                ->setLp($lp)
                ->setRoot($root)
                ->setParent($parent)
                ->setDisplayOrder($order)
                ->setTitle(mb_substr($title, 0, 511))
                ->setItemType($itemType)
                ->setRef($identifier)
                ->setPath($href)
                ->setMinScore(0.0)
                ->setMaxScore(
                    \is_float($manifestItem['maxScore'] ?? null) || \is_int($manifestItem['maxScore'] ?? null)
                        ? (float) $manifestItem['maxScore']
                        : ($useMaxScore ? 100.0 : null),
                )
                ->setPrerequisite((string) ($manifestItem['prerequisites'] ?? ''))
                ->setLaunchData((string) ($manifestItem['launchData'] ?? ''))
                ->setParameters((string) ($manifestItem['parameters'] ?? ''))
            ;

            $masteryScore = $manifestItem['masteryScore'] ?? null;
            if (\is_float($masteryScore) || \is_int($masteryScore)) {
                $item->setMasteryScore((float) $masteryScore);
            }
            $maxTimeAllowed = $manifestItem['maxTimeAllowed'] ?? null;
            if (\is_string($maxTimeAllowed) && '' !== $maxTimeAllowed) {
                $item->setMaxTimeAllowed($maxTimeAllowed);
            }

            $this->entityManager->persist($item);

            $children = $manifestItem['children'] ?? [];
            if (\is_array($children) && [] !== $children) {
                $this->createItems(
                    $lp,
                    $root,
                    $item,
                    $children,
                    $resources,
                    $useMaxScore,
                    $order,
                );
            }
        }
    }

    private function buildPackageRoot(string $originalName, int $organizationIndex, string $assetId): string
    {
        $base = (string) pathinfo($originalName, PATHINFO_FILENAME);
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $base), '-_.'));
        if ('' === $base) {
            $base = 'scorm-package';
        }

        return $base.'-'.($organizationIndex + 1).'-'.substr(str_replace('-', '', $assetId), 0, 8);
    }
}
