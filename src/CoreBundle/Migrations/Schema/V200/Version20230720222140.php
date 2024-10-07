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

            // Define the regular expression pattern to match URLs containing "my_files/"
            $pattern = '/(href|src)="[^"]*\/users\/(\d+)\/\d+\/my_files\/([^"]+)"/i';
            preg_match_all($pattern, $content, $matches);

            // Combine the URLs found in href and src attributes
            $allUrls = array_merge($matches[0]);

            if (!empty($allUrls)) {
                foreach ($allUrls as $url) {
                    // Define a regular expression to search for the "/upload/users" part, numeric values, and filename in the URL
                    $pattern = '/\/upload\/users\/(\d+)\/(\d+)\/my_files\/([^\/"]+)/i';

                    // Perform the regular expression search in the URL
                    if (preg_match($pattern, $url, $matches)) {
                        $folderId = (int) $matches[1];
                        $userId = (int) $matches[2];
                        $filename = $matches[3];

                        // Get the full file path
                        $filePath = $this->getUpdateRootPath()."/app/upload/users/{$folderId}/{$userId}/my_files/{$filename}";

                        // Check if the path corresponds to a file
                        if (is_file($filePath)) {
                            // Output the user id, folder id, and filename
                            error_log('User ID: '.$userId.', Folder ID: '.$folderId.', Filename: '.$filename);
                            $user = $userRepo->find($userId);

                            if (!$user) {
                                $user = $fallbackUser;
                            }

                            $personalFile = $personalRepo->getResourceByCreatorFromTitle(
                                $filename,
                                $user,
                                $user->getResourceNode()
                            );

                            $newUrl = $personalRepo->getResourceFileUrl($personalFile);
                            if (!empty($newUrl)) {
                                // Perform the replacement of the old URL with the new URL in the content
                                $content = preg_replace('/(src|href)="[^"]*\/users\/(\d+)\/\d+\/my_files\/([^"]+)"/i', '$1="'.$newUrl.'"', $content);
                            }
                        }
                    }
                }

                // Set the updated content back to the social post entity
                $socialPost->setContent($content);

                // Persist the updated social post entity
                $this->entityManager->persist($socialPost);
                $this->entityManager->flush();
            }
        }
    }
}
