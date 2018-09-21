<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = "SELECT * FROM c_item_property WHERE tool = 'document' LIMIT 1";
$result = Database::query($sql);

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $row['iid'];
    $courseId = $row['c_id'];
    $sessionId = $row['session_id'];
    $groupId = $row['to_group_id'];
    $toUserId = $row['to_user_id'];

    $toUser = api_get_user_entity($toUserId);
    $author = api_get_user_entity($row['insert_user_id']);
    $createdAt = api_get_utc_datetime($row['insert_date'], true, true);
    $lastUpdatedAt = api_get_utc_datetime($row['lastedit_date'], true, true);

    $em = Database::getManager();

    switch ($row['tool']) {
        case 'document':
            $course = api_get_course_entity($courseId);
            $session = api_get_course_entity($sessionId);
            $group = api_get_group_entity($groupId);

            $documentData = DocumentManager::get_document_data_by_id($row['ref'], $course->getCode(), $sessionId);
            if (!$documentData) {
                error_log('Skipped');
                continue 2;
            }

            $file = new ResourceFile();
            $file
                ->setName($documentData['title'])
                ->setOriginalFilename(basename($documentData['path']))
                ->setSize($documentData['size'])
            ;

            $resourceType = $em->getRepository('ChamiloCoreBundle:ResourceType')->findOneBy(['name' => 'document']);

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
| announcement              |
| attendance                |
| blog_management           |
| calendar_event            |
| calendar_event_attachment |
| course_description        |
| document                  |
| dropbox                   |
| forum                     |
| forum_attachment          |
| forum_category            |
| forum_post                |
| forum_thread              |
| glossary                  |
| learnpath                 |
| learnpath_category        |
| link                      |
| link_category             |
| notebook                  |
| quiz                      |
| survey                    |
| test_category             |
| thematic                  |
| thematic_advance          |
| thematic_plan             |
| wiki                      |
| work                      |

 */
