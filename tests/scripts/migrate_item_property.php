<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;

use Chamilo\MediaBundle\Entity\Media;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = "SELECT * FROM c_item_property WHERE tool = 'document' LIMIT 10";
$result = Database::query($sql);

$fs = Container::$container->get('oneup_flysystem.courses_filesystem');

$em = Database::getManager();
$resourceType = $em->getRepository('ChamiloCoreBundle:Resource\ResourceType')->findOneBy(['name' => 'document']);
$coursePath = api_get_path(SYS_PATH).'app/courses/';
$mediaManager = Container::$container->get('sonata.media.manager.media');

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $itemIid = $row['iid'];
    $courseId = $row['c_id'];
    $sessionId = $row['session_id'];
    $groupId = $row['to_group_id'];
    $toUserId = $row['to_user_id'];

    $toUser = api_get_user_entity($toUserId);
    $author = api_get_user_entity($row['insert_user_id']);
    $createdAt = api_get_utc_datetime($row['insert_date'], true, true);
    $lastUpdatedAt = api_get_utc_datetime($row['lastedit_date'], true, true);

    $course = api_get_course_entity($courseId);
    $session = api_get_course_entity($sessionId);
    $group = api_get_group_entity($groupId);

    switch ($row['tool']) {
        case 'document':
            $documentData = DocumentManager::get_document_data_by_id($row['ref'], $course->getCode(), $sessionId);
            var_dump($documentData);
            if (!$documentData) {
                //$documentData = DocumentManager::get_document_data_by_id($row['ref'], $course->getCode(), $sessionId);
                error_log("Skipped item property iid #$itemIid");
                continue 2;
            }

            $folderPath = $course->getDirectory().'/document/'.$documentData['path'];

            $file = $coursePath.$folderPath;

            switch ($documentData['filetype']) {
                case 'folder':
                    //$fs->createDir($folderPath);
                    break;
                case 'file':
                    //$stream = fopen($file, 'r+');
                    //$fs->writeStream($folderPath, $stream);
                    //fclose($stream);
                    /** @var Media $media */
                    $media = $mediaManager->create();
                    //$media = new Media();
                    $media->setName($documentData['title']);
                    $media->setSize($documentData['size']);
                    $media->setContext('default');
                    $media->setProviderName('sonata.media.provider.image');
                    var_dump($file);
                    $media->setEnabled(true);
                    $stdFile = new Std
                    $media->setBinaryContent(file_get_contents($file));

                    $mediaManager->save($media, true);



                    break;
            }

            continue;

            $file = new ResourceFile();
            $file
                ->setHash('')
                ->setName($documentData['title'])
                ->setOriginalFilename(basename($documentData['path']))
                ->setSize($documentData['size'])
            ;

            $node = new ResourceNode();
            $node
                ->setName($documentData['title'])
                ->setDescription($documentData['comment'])
                ->setResourceFile($file)
                ->setCreator($author)
                ->setResourceFile($file)
                ->setResourceType($resourceType)

                ->setCreatedAt($createdAt)
                ->setUpdatedAt($lastUpdatedAt)
            ;
            $em->persist($node);
            $em->flush();

            $rights = [];
            switch ($row['visibility']) {
                case '0':
                    $newVisibility = ResourceLink::VISIBILITY_DRAFT;

                    $readerMask = ResourceNodeVoter::getReaderMask();
                    $editorMask = ResourceNodeVoter::getEditorMask();

                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($editorMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                    ;
                    $rights[] = $resourceRight;
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
