<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

require_once '../../inc/global.inc.php';

ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', '1000');

api_set_more_memory_and_time_limits();

api_protect_course_script();

// DISABLED: the script let any course member push an arbitrary work id to the external service. The body below
// was rewritten on the 2.x work model (CStudentPublication and the file of its resource node, instead of the
// removed c_student_publication.id/c_id/url columns and the course directory) but it could NOT be run against
// the Compilatio service, so it is not verified end to end. Before removing the denial: restrict the script to
// teachers (api_is_allowed_to_edit()), then try it against a Compilatio account.
api_not_allowed(true);
exit;

$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
$compilatio = new Compilatio();

/* if we have to upload severals documents*/
if (isset($_REQUEST['type']) && 'multi' === $_REQUEST['type']) {
    $docs = explode('a', $_REQUEST['doc']);
    for ($k = 0; $k < count($docs) - 1; $k++) {
        $documentId = 0;
        if (isset($docs[$k])) {
            $documentId = (int) $docs[$k];
        }

        $compilatioId = $compilatio->getCompilatioId($documentId, $courseId);
        if (empty($compilatioId)) {
            $doc = compilatioGetWork($documentId);
            if ($doc) {
                /*We load the document in compilatio through the webservice */
                $compilatioId = compilatioSendWork($compilatio, $doc);
                /*we associate in the database the document chamilo to the document compilatio*/
                /*we verify that the docmuent's id is an hash_md5*/
                if (Compilatio::isMd5($compilatioId)) {
                    $compilatio->saveDocument($courseId, $documentId, $compilatioId);
                    $soapRes = $compilatio->startAnalyse($compilatioId);
                }
            }
        }
    }
} else {
    $documentId = isset($_GET['doc']) ? $_GET['doc'] : 0;
    sendDocument($documentId, $courseInfo);
}

function sendDocument($documentId, $courseInfo)
{
    if (empty($courseInfo)) {
        return false;
    }

    $courseId = $courseInfo['real_id'] ?? 0;
    $documentId = (int) $documentId;

    if (empty($courseId) || empty($documentId)) {
        return false;
    }

    $doc = compilatioGetWork($documentId);
    if (null === $doc) {
        echo Display::return_message(get_lang('Error'), 'error');

        return false;
    }

    $compilatio = new Compilatio();
    $compilatioId = compilatioSendWork($compilatio, $doc);

    if (Compilatio::isMd5($compilatioId)) {
        $compilatio->saveDocument($courseId, $documentId, $compilatioId);
        $compilatio->startAnalyse($compilatioId);
        echo Display::return_message(get_lang('Uploaded.'));
    } else {
        echo Display::return_message(get_lang('Error'), 'error');
    }
}

/**
 * The submission of the current course with this id and the file of its resource node, or null when the work does
 * not exist in the course or has no file (a folder): title, original file name, mime type, content and the
 * absolute URL of the file.
 */
function compilatioGetWork(int $workId): ?array
{
    $work = Container::getEntityManager()->createQueryBuilder()
        ->select('w')
        ->from(CStudentPublication::class, 'w')
        ->innerJoin('w.resourceNode', 'node')
        ->innerJoin('node.resourceLinks', 'link')
        ->where('w.iid = :id')
        ->andWhere('IDENTITY(link.course) = :courseId')
        ->setParameter('id', $workId)
        ->setParameter('courseId', (int) api_get_course_int_id())
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult()
    ;
    if (!$work instanceof CStudentPublication) {
        return null;
    }

    $node = $work->getResourceNode();
    $file = $node->getFirstResourceFile();
    if (null === $file) {
        return null;
    }

    $repository = Container::getResourceNodeRepository();
    $filename = (string) $file->getOriginalName();

    return [
        'title' => $work->getTitle(),
        'filename' => $filename,
        'mime' => (string) ($file->getMimeType() ?: DocumentManager::file_get_mime_type($filename)),
        'content' => $repository->getResourceNodeFileContent($node, $file),
        'url' => $repository->getResourceFileUrl(
            $node,
            ['mode' => 'download'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            $file
        ),
    ];
}

/**
 * Sends the work to Compilatio, by URL (wget transport) or by SOAP (the content), and returns what the service
 * answered (the document hash on success).
 */
function compilatioSendWork(Compilatio $compilatio, array $doc)
{
    if ('wget' === $compilatio->getTransportMode()) {
        /*Compilatio's server recover the file throught wget like this:
        username:password@http://somedomain.com/reg/remotefilename.tar.gz */
        $filename = $doc['url'];
        if (strlen($compilatio->getWgetLogin()) > 2) {
            $filename = $compilatio->getWgetLogin().':'.$compilatio->getWgetPassword().'@'.$filename;
        }

        return $compilatio->sendDoc($doc['title'], '', $filename, 'text/plain', 'get_url');
    }

    /* we use strictly the SOAP for the data trasmission */
    return $compilatio->sendDoc($doc['title'], '', $doc['filename'], $doc['mime'], $doc['content']);
}
