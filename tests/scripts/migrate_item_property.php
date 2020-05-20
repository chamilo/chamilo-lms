<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\MediaBundle\Entity\Media;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;

/**
 * Migrate content from c_item_property and c_document tables to the new "Resource" system.
 *
 */

echo 'First check if table "classification__category" has a default category; if not then run: <br />';
echo 'bin/console sonata:media:fix-media-context';
echo 'change course id in the query';
//exit;
// For tests to clean all resource stuff:
//

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = "SELECT
          d.id,
          d.c_id,
          d.session_id,
          i.to_group_id,
          i.to_user_id,
          i.iid,
          insert_user_id,
          insert_date,
          lastedit_date,
          tool,
          visibility
        FROM c_item_property i
        INNER JOIN c_document d
        ON (d.iid = i.ref AND i.c_id = d.c_id)
        WHERE
            i.tool = 'document' AND
            d.c_id = 12
        ORDER BY d.path";
$result = Database::query($sql);

$em = Database::getManager();
$resourceType = $em->getRepository('ChamiloCoreBundle:ResourceType')->findOneBy(['name' => 'document']);
$coursePath = api_get_path(SYS_PATH).'app/courses/';
$mediaManager = Container::$container->get('sonata.media.manager.media');
$documentManager = $em->getRepository('ChamiloCourseBundle:CDocument');
$contextManager = Container::$container->get('sonata.classification.manager.context');
$defaultContext = $contextManager->findOneBy(['id' => 'default']);

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $itemIid = $row['iid'];
    $courseId = $row['c_id'];
    $sessionId = $row['session_id'];
    $groupId = $row['to_group_id'];
    $toUserId = $row['to_user_id'];
    $documentId = $row['id'];

    $toUser = api_get_user_entity($toUserId);
    $author = api_get_user_entity($row['insert_user_id']);

    if (empty($author)) {
        error_log("User does not exists in the DB ".$row['insert_user_id']);
        continue;
    }

    $createdAt = api_get_utc_datetime($row['insert_date'], true, true);
    $lastUpdatedAt = api_get_utc_datetime($row['lastedit_date'], true, true);

    $course = api_get_course_entity($courseId);
    if (empty($course)) {
        error_log("Course does not exists in the DB $courseId");
        continue;
    }
    $session = api_get_session_entity($sessionId);
    $group = api_get_group_entity($groupId);

    switch ($row['tool']) {
        case 'document':
            $documentData = DocumentManager::get_document_data_by_id($documentId, $course->getCode(), true, $sessionId);
            if (!$documentData) {
                //$documentData = DocumentManager::get_document_data_by_id($row['ref'], $course->getCode(), $sessionId);
                error_log("Skipped item property iid #$itemIid");
                continue 2;
            }

            $folderPath = $course->getDirectory().'/document/'.$documentData['path'];
            $file = $coursePath.$folderPath;
            $document = $documentManager->find($documentData['iid']);
            var_dump('Parsing document iid #'.$document->getIid());

            // Find parent node
            $parentNode = null;
            if (!empty($documentData['parent_id'])) {
                /** @var CDocument $parentDocument */
                $parentDocument = $documentManager->find($documentData['parent_id']);
                $parentNode = $parentDocument->getResourceNode();
            }

            // Creating node
            $node = new ResourceNode();
            $node
                ->setName($documentData['title'])
                ->setDescription($documentData['comment'] ?? '')
                ->setCreator($author)
                ->setParent($parentNode)
                ->setResourceType($resourceType)
                ->setCreatedAt($createdAt)
                ->setUpdatedAt($lastUpdatedAt)
            ;

            $em->persist($node);
            $document->setResourceNode($node);
            $em->persist($document);

            $rights = [];
            switch ($row['visibility']) {
                case '0':
                    $newVisibility = ResourceLink::VISIBILITY_DRAFT;
                    $editorMask = ResourceNodeVoter::getEditorMask();

                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($editorMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                    ;
                    $rights[] = $resourceRight;

                    /*$resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($readerMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT)
                    ;
                    $rights[] = $resourceRight;*/
                    break;
                case '1':
                    $newVisibility = ResourceLink::VISIBILITY_PUBLISHED;
                    break;
                case '2':
                    $newVisibility = ResourceLink::VISIBILITY_DELETED;
                    break;
            }

            $link = new ResourceLink();
            $link
                ->setCourse($course)
                ->setSession($session)
                ->setGroup($group)
                ->setUser($toUser)
                ->setResourceNode($node)
                ->setVisibility($newVisibility)
            ;

            if (!empty($rights)) {
                foreach ($rights as $right) {
                    $link->addResourceRight($right);
                }
            }

            switch ($documentData['filetype']) {
                case 'folder':
                    // Folder doesn't need a ResourceFile or Media entity
                    break;
                case 'file':
                    /** @var Media $media */
                    $media = $mediaManager->create();
                    $media->setName($documentData['title']);

                    $fileName = basename($documentData['path']);
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                    $media->setSize($documentData['size']);
                    $media->setContext('default');

                    $provider = 'sonata.media.provider.file';
                    if (in_array($extension, ['jpeg', 'jpg', 'gif', 'png'])) {
                        $provider = 'sonata.media.provider.image';
                    }

                    $media->setProviderName($provider);
                    $media->setEnabled(true);
                    $media->setBinaryContent($file);

                    $mediaManager->save($media, true);

                    $resourceFile = new ResourceFile();
                    $resourceFile->setMedia($media);
                    $resourceFile->setName($documentData['title']);
                    $node->setResourceFile($resourceFile);

                    $em->persist($resourceFile);
                    $em->persist($node);
                    break;
            }

            $em->persist($link);
            $em->flush();
            break;
    }
}

/**
default resource type list
| blog_management           | blog
| calendar_event            |
| calendar_event_attachment |
| course_description        |
| document                  |
| dropbox                   |
 *
 *
 *
| forum                     |
| forum_attachment          |
| forum_category            |
| forum_post                |
| forum_thread              |
 *
| glossary                  |
 *
 *
| link                      |
| link_category             |
 * -
| test_category             |
| thematic                  |
| thematic_advance          |
| thematic_plan             |
| work                      |

 */
