<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20230720222140 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add table social_post_attachments and rename my_files path by resource file path';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $userRepo = $this->container->get(UserRepository::class);
        $personalRepo = $this->container->get(PersonalFileRepository::class);
        $fallbackUser = $userRepo->findOneBy(['status' => User::ROLE_FALLBACK], ['id' => 'ASC']);

        $q = $this->entityManager->createQuery('SELECT s FROM Chamilo\CoreBundle\Entity\SocialPost s');

        /** @var SocialPost $socialPost */
        foreach ($q->toIterable() as $socialPost) {
            $content = $socialPost->getContent();

            // Each link is replaced by the URL of its own file, so a post with several files keeps them all
            $pattern = '/(href|src)="[^"]*(\/upload\/users\/(?:\d+\/)?(\d+)\/my_files\/([^\/"]+))"/i';
            $updatedContent = preg_replace_callback(
                $pattern,
                function (array $matches) use ($userRepo, $personalRepo, $fallbackUser): string {
                    $userId = (int) $matches[3];
                    // Links are URL-encoded ("LOGO%20TRANSPARENT.jpg"), the file and its title are not
                    $filename = urldecode($matches[4]);

                    if (!is_file($this->getUpdateRootPath().'/app'.urldecode($matches[2]))) {
                        return $matches[0];
                    }

                    error_log('User ID: '.$userId.', Filename: '.$filename);
                    $user = $userRepo->find($userId) ?? $fallbackUser;

                    $personalFile = $personalRepo->getResourceByCreatorFromTitle(
                        $filename,
                        $user,
                        $user->getResourceNode()
                    );
                    if (null === $personalFile) {
                        return $matches[0];
                    }

                    $newUrl = $personalRepo->getResourceFileUrl($personalFile);
                    if (empty($newUrl)) {
                        return $matches[0];
                    }

                    return $matches[1].'="'.$newUrl.'"';
                },
                $content
            );

            if (null !== $updatedContent && $updatedContent !== $content) {
                // Set the updated content back to the social post entity
                $socialPost->setContent($updatedContent);

                // Persist the updated social post entity
                $this->entityManager->persist($socialPost);
                $this->entityManager->flush();
            }
        }
    }
}
