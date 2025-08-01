<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;

/**
 * Responses to AJAX calls for the document upload.
 */
require_once __DIR__.'/../global.inc.php';

$repo = Container::getDocumentRepository();

$action = $_REQUEST['a'];
switch ($action) {
    case 'get_dir_size':
        api_protect_course_script(true);
        $path = isset($_GET['path']) ? $_GET['path'] : '';
        $isAllowedToEdit = api_is_allowed_to_edit();
        $size = $repo->getFolderSize(api_get_course_int_id(), $path);

        echo format_file_size($size);
        break;
    case 'get_document_quota':
        // Getting the course quota
        $courseQuota = DocumentManager::get_course_quota();

        // Calculating the total space
        $total = $repo->getTotalSpace(api_get_course_int_id());

        // Displaying the quota
        echo DocumentManager::displaySimpleQuota($courseQuota, $total);
        break;
    case 'document_preview':
        $course = api_get_course_entity($_REQUEST['course_id']);
        if (null !== $course) {
            echo DocumentManager::get_document_preview(
                $course,
                false,
                '_blank',
                $_REQUEST['session_id']
            );
        }
        break;
    case 'document_destination':
        //obtained the bootstrap-select selected value via ajax
        $dirValue = isset($_POST['dirValue']) ? $_POST['dirValue'] : null;
        echo Security::remove_XSS($dirValue);
        break;
    case 'upload_file':
        api_protect_course_script(true);

        $ifExists = $_POST['if_exists'] ?? api_get_setting('document.document_if_file_exists_option') ?? 'rename';
        $unzip    = !empty($_POST['unzip']);

        if (isset($_REQUEST['chunkAction']) && $_REQUEST['chunkAction'] === 'send') {
            if (!empty($_FILES['files'])) {
                $tempDir  = api_get_path(SYS_ARCHIVE_PATH);
                $files    = $_FILES['files'];
                $fileList = [];
                foreach ($files['name'] as $i => $name) {
                    $fileList[$i] = [
                        'name'     => $name,
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                }
                foreach ($fileList as $chunk) {
                    $safeName = disable_dangerous_file(api_replace_dangerous_char($chunk['name']));
                    file_put_contents(
                        $tempDir . $safeName,
                        fopen($chunk['tmp_name'], 'r'),
                        FILE_APPEND
                    );
                }
            }
            echo json_encode(['files' => $_FILES, 'errorStatus' => 0]);
            exit;
        }

        $isEdit = api_is_allowed_to_edit(null, true);
        if (!$isEdit) {
            exit;
        }

        $directoryParentId = isset($_POST['directory_parent_id'])
            ? (int) $_POST['directory_parent_id']
            : 0;

        $toProcess = [];
        if (!empty($_FILES['files'])) {
            foreach ($_FILES['files']['name'] as $i => $name) {
                if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                    $toProcess[] = [
                        'name'     => $name,
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'type'     => $_FILES['files']['type'][$i],
                        'size'     => $_FILES['files']['size'][$i],
                    ];
                }
            }
        }

        $repo    = Container::getDocumentRepository();
        $em      = Database::getManager();
        $course  = api_get_course_entity();
        $results = [];

        $createDocument = function(string $path, string $filename, string $mimetype, int $filesize, bool $ifExists) use (
            $repo, $em, $course, $directoryParentId, &$results
        ) {
            $qb = $em->createQueryBuilder()
                ->select('d')
                ->from(CDocument::class, 'd')
                ->innerJoin('d.resourceNode', 'n')
                ->andWhere('d.title = :title')
                ->andWhere('n.parent = :parentNodeId')
                ->setParameters([
                    'title'        => $filename,
                    'parentNodeId' => $directoryParentId
                ])
                ->setMaxResults(1);
            $existing = $qb->getQuery()->getOneOrNullResult();

            if ($existing) {
                if ($ifExists === 'nothing') {
                    return;
                }
                if ($ifExists === 'overwrite') {
                    $em->remove($existing);
                    $em->flush();
                }
                if ($ifExists === 'rename') {
                    $parts   = pathinfo($filename);
                    $filename = $parts['filename'] . '_' . uniqid()
                        . (isset($parts['extension']) ? '.' . $parts['extension'] : '');
                }
            }

            $doc = new CDocument();
            $doc->setTitle($filename)
                ->setFiletype('file')
                ->setComment('')
                ->setReadonly(false)
                ->setCreator(api_get_user_entity())
                ->setParent($course)
                ->addCourseLink($course);

            $em->persist($doc);
            $em->flush();

            $repo->addFileFromPath($doc, $filename, $path);

            $results[] = [
                'name'   => api_htmlentities($doc->getTitle()),
                'url'    => $repo->getResourceFileUrl($doc),
                'size'   => format_file_size($filesize),
                'type'   => api_htmlentities($mimetype),
                'result' => Display::return_icon('accept.png', get_lang('Uploaded'))
            ];
        };

        foreach ($toProcess as $fileInfo) {
            $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
            if ($unzip && $ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($fileInfo['tmp_name']) === true) {
                    $extractPath = sys_get_temp_dir() . '/extracted_' . uniqid();
                    mkdir($extractPath);
                    $zip->extractTo($extractPath);
                    $zip->close();

                    $it = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($extractPath, \FilesystemIterator::SKIP_DOTS)
                    );
                    foreach ($it as $f) {
                        if ($f->isFile()) {
                            $createDocument(
                                $f->getRealPath(),
                                $f->getFilename(),
                                mime_content_type($f->getRealPath()),
                                $f->getSize(),
                                $ifExists
                            );
                        }
                    }
                    continue;
                }
            }

            $createDocument(
                $fileInfo['tmp_name'],
                $fileInfo['name'],
                $fileInfo['type'],
                $fileInfo['size'],
                $ifExists
            );
        }

        header('Content-Type: application/json');
        echo json_encode(['files' => $results]);
        exit;
}
exit;
