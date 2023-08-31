<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20230720143000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate my_files of users to personal_files ';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $q = $em->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\User u');
        /** @var User $userEntity */
        foreach ($q->toIterable() as $userEntity) {
            $id = $userEntity->getId();
            $path = "users/{$id}/";

            $variable = 'split_users_upload_directory';
            // Query the 'selected_value' from the 'settings_current' table where the 'variable' is 'split_users_upload_directory'
            $query = $em->createQuery('SELECT s.selectedValue FROM Chamilo\CoreBundle\Entity\SettingsCurrent s WHERE s.variable = :variable')
                ->setParameter('variable', $variable)
            ;

            // Get the result of the query (it should return a single row with the 'selected_value' column)
            $result = $query->getOneOrNullResult();
            $settingValueAsString = 'false';
            if (null !== $result) {
                // Convert the 'selected_value' to a string and store it in $settingValueAsString
                $settingValue = $result['selectedValue'];
                $settingValueAsString = (string) $settingValue;
            }

            // If the 'split_users_upload_directory' setting is 'true', adjust the path accordingly
            if ('true' === $settingValueAsString) {
                $path = 'users/'.substr((string) $id, 0, 1).'/'.$id.'/';
            }

            $baseDir = $rootPath.'/app/upload/'.$path;

            // Check if the base directory exists, if not, continue to the next user
            if (!is_dir($baseDir)) {
                continue;
            }

            // Get all the files in the 'my_files' directory
            $myFilesDir = $baseDir.'my_files/';
            $files = glob($myFilesDir.'*');

            // $files now contains a list of all files in the 'my_files' directory
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $title = basename($file);
                $queryBuilder = $em->createQueryBuilder();

                // Build the query to join the ResourceNode and PersonalFile tables
                $queryBuilder
                    ->select('p')
                    ->from(ResourceNode::class, 'n')
                    ->innerJoin(PersonalFile::class, 'p', Join::WITH, 'p.resourceNode = n.id')
                    ->where('n.title = :title')
                    ->andWhere('n.creator = :creator')
                    ->setParameter('title', $title)
                    ->setParameter('creator', $id)
                ;

                $result = $queryBuilder->getQuery()->getOneOrNullResult();

                if ($result) {
                    // Skip creating a new entity and log a message
                    error_log('MIGRATIONS :: $file -- '.$file.' (Skipped: Already exists) ...');

                    continue;
                }

                error_log('MIGRATIONS :: $file -- '.$file.' ...');
                // Create a new PersonalFile entity if it doesn't already exist
                $personalFile = new PersonalFile();
                $personalFile->setTitle($title); // Set the file name as the title
                $personalFile->setCreator($userEntity);
                $personalFile->setParentResourceNode($userEntity->getResourceNode()->getId());
                $personalFile->setResourceName($title);
                $mimeType = mime_content_type($file);
                $uploadedFile = new UploadedFile($file, $title, $mimeType, null, true);
                $personalFile->setUploadFile($uploadedFile);

                // Save the object to the database
                $em->persist($personalFile);
                $em->flush();
            }
        }
    }
}
