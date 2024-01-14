<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\SocialPostAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use DateTimeZone;
use DirectoryIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20231026231100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate message_attachments to social_post_attachments';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $repo = $container->get(SocialPostAttachmentRepository::class);
        $userRepo = $container->get(UserRepository::class);
        $admin = $this->getAdmin();

        $sub = $em->createQueryBuilder();
        $sub->select('sp.id')
            ->from('Chamilo\CoreBundle\Entity\SocialPost', 'sp')
        ;

        $qb = $em->createQueryBuilder();
        $qb->select('ma')
            ->from('Chamilo\CoreBundle\Entity\MessageAttachment', 'ma')
            ->where($qb->expr()->in('ma.message', $sub->getDQL()))
        ;

        $query = $qb->getQuery();
        $messageAttachments = $query->getResult();

        foreach ($messageAttachments as $attachment) {
            $message = $attachment->getMessage();
            if ($message) {
                $messageId = $message->getId();
                $filename = $attachment->getFilename();
                $rootDir = $rootPath.'/app/upload/users';
                $targetFile = $attachment->getPath();
                $foundFilePath = $this->findFileRecursively($rootDir, $targetFile);
                $sender = $message->getSender();

                if ($foundFilePath) {
                    error_log("File found in $foundFilePath");

                    $mimeType = mime_content_type($foundFilePath);
                    $uploadFile = new UploadedFile($foundFilePath, $filename, $mimeType, null, true);

                    $socialPost = $em->getRepository(SocialPost::class)->find($messageId);

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

                    $em->persist($attachment);
                    $em->flush();

                    $repo->addFile($attachment, $uploadFile);
                }
            }
        }
    }

    private function findFileRecursively(string $directory, string $targetFile): ?string
    {
        if (!is_dir($directory)) {
            return null;
        }

        foreach (new DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $filePath = $fileInfo->getPathname();

            if ($fileInfo->isDir()) {
                $result = $this->findFileRecursively($filePath, $targetFile);
                if (null !== $result) {
                    return $result;
                }
            } else {
                if (str_contains($fileInfo->getFilename(), $targetFile)) {
                    return $filePath;
                }
            }
        }

        return null;
    }
}
