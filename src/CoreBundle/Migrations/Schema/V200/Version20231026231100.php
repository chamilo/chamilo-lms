<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\SocialPostAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\SocialPostAttachmentRepository;
use Chamilo\CoreBundle\Repository\SocialPostRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

final class Version20231026231100 extends AbstractMigrationChamilo
{
    private const CLEAR_BATCH_SIZE = 25;

    public function getDescription(): string
    {
        return 'Migrate message_attachments to social_post_attachments using one filesystem index';
    }

    public function up(Schema $schema): void
    {
        /** @var SocialPostAttachmentRepository $repo */
        $repo = $this->container->get(SocialPostAttachmentRepository::class);

        /** @var SocialPostRepository $socialPostRepo */
        $socialPostRepo = $this->container->get(SocialPostRepository::class);

        $rootDir = $this->getUpdateRootPath().'/app/upload/users';
        $fileIndex = $this->buildFileIndex($rootDir);

        $sub = $this->entityManager->createQueryBuilder();
        $sub->select('sp.id')->from('Chamilo\CoreBundle\Entity\SocialPost', 'sp');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('ma')
            ->from('Chamilo\CoreBundle\Entity\MessageAttachment', 'ma')
            ->where($qb->expr()->in('ma.message', $sub->getDQL()))
            ->orderBy('ma.id', 'ASC')
        ;

        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $skipped = 0;

        foreach ($qb->getQuery()->toIterable() as $legacyAttachment) {
            ++$seen;
            $message = $legacyAttachment->getMessage();
            if (!$message) {
                ++$skipped;

                continue;
            }

            $messageId = (int) $message->getId();
            $filename = trim((string) $legacyAttachment->getFilename());
            $targetFile = trim((string) $legacyAttachment->getPath());
            $foundFilePath = $this->resolveIndexedFile($fileIndex, $targetFile, $filename);
            $sender = $message->getSender();

            if (null === $foundFilePath || !$sender) {
                ++$missing;

                continue;
            }

            $socialPost = $socialPostRepo->find($messageId);
            if (!$socialPost) {
                ++$skipped;

                continue;
            }

            // Restart-safe duplicate guard.
            $existing = $repo->findOneBy([
                'socialPost' => $socialPost,
                'filename' => $filename,
            ]);
            if ($existing instanceof SocialPostAttachment) {
                ++$skipped;

                continue;
            }

            $mimeType = mime_content_type($foundFilePath) ?: 'application/octet-stream';
            $uploadFile = new UploadedFile($foundFilePath, $filename, $mimeType, null, true);

            $attachment = new SocialPostAttachment();
            $attachment->setSocialPost($socialPost);
            $attachment->setPath(uniqid('social_post', true));
            $attachment->setFilename($uploadFile->getClientOriginalName());
            $attachment->setSize($uploadFile->getSize());
            $attachment->setInsertUserId($sender->getId());
            $attachment->setInsertDateTime(new DateTime('now', new DateTimeZone('UTC')));
            $attachment->setParent($sender);
            $attachment->addUserLink($sender);
            $attachment->setCreator($sender);

            // The resource node must exist before the repository stores the file.
            $this->entityManager->persist($attachment);
            $this->entityManager->flush();
            $repo->addFile($attachment, $uploadFile);
            $this->entityManager->flush();
            ++$migrated;

            if (($migrated % self::CLEAR_BATCH_SIZE) === 0) {
                $this->entityManager->clear();
                $this->getLogger()->info('Social post attachment migration progress.', [
                    'seen' => $seen,
                    'migrated' => $migrated,
                    'missing_files' => $missing,
                    'skipped' => $skipped,
                ]);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Social post attachment migration completed.', [
            'indexed_files' => \count($fileIndex),
            'seen' => $seen,
            'migrated' => $migrated,
            'missing_files' => $missing,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function buildFileIndex(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $index = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();
                $name = $file->getFilename();
                $index[$name] ??= $path;
                $index[basename($path)] ??= $path;
            }
        } catch (Throwable $e) {
            $this->getLogger()->warning('Could not index legacy user uploads.', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
        }

        return $index;
    }

    /**
     * @param array<string, string> $index
     */
    private function resolveIndexedFile(array $index, string $targetFile, string $filename): ?string
    {
        $candidates = array_values(array_unique(array_filter([
            basename($targetFile),
            $targetFile,
            basename($filename),
            $filename,
        ])));

        foreach ($candidates as $candidate) {
            if (isset($index[$candidate])) {
                return $index[$candidate];
            }
        }

        return null;
    }
}
