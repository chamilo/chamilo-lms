<?php

/* For licensing terms, see /license.txt */

/**
 * Script to restore some deleted documents
 */

exit;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CItemProperty;

require __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();

$cId = 0;
$path = '%_DELETED_%';

//// --->

$currentUserId = api_get_user_id();
$course = api_get_course_entity($cId);
$courseDirectory = api_get_path(SYS_COURSE_PATH).$course->getDirectory().'/document';

$documents = $em
    ->createQuery(
        'SELECT d FROM ChamiloCourseBundle:CDocument d
        WHERE d.cId = :cid AND d.path LIKE :path AND d.sessionId = 0'
    )
    ->setParameters(['cid' => (int) $cId, 'path' => $path])
    ->getResult();

header('Content-Type: text/plain');

/** @var CDocument $document */
foreach ($documents as $document) {
    $properties = $em
        ->createQuery(
            'SELECT i FROM ChamiloCourseBundle:CItemProperty i
            WHERE i.course = :course AND i.session IS NULL AND i.ref = :ref'
        )
        ->setParameters(['course' => $course, 'ref' => $document->getIid()])
        ->getResult();

    /** @var CItemProperty $property */
    foreach ($properties as $property) {
        if (!in_array($property->getLasteditType(), ['DocumentDeleted', 'delete'])) {
            continue;
        }
        switch ($property->getLasteditType()) {
            case 'DocumentDeleted':
                echo "Changing 'deleted' log to 'added' log".PHP_EOL;
                $property
                    ->setLasteditType('DocumentAdded')
                    ->setLasteditUserId($currentUserId)
                    ->setLasteditDate(
                        api_get_utc_datetime(null, false, true)
                    )
                    ->setVisibility(1);

                $em->persist($property);
                break;
            case 'delete':
                echo "Removing delete log".PHP_EOL;
                $em->remove($property);
                break;
        }
    }

    $filePath = $courseDirectory.$document->getPath();
    $newPath = str_replace('_DELETED_'.$document->getIid(), '', $document->getPath());
    $newFilePath = $courseDirectory.$newPath;

    $document->setPath($newPath);
    $em->flush();

    $renaming = rename($filePath, $newFilePath);

    echo "Renaming $filePath to $newFilePath : ";
    echo $renaming ? 'y' : 'n';
    echo PHP_EOL;
}
