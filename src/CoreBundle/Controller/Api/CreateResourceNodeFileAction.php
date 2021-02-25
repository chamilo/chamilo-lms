<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceNodeFileAction
{
    public function __invoke(Request $request): CDocument
    {
        error_log('CreateResourceNodeFileAction __invoke');

        $contentData = $request->getContent();
        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            //error_log(print_r($contentData, 1));
            $title = $contentData['title'];
            $comment = $contentData['comment'];
        } else {
            $title = $request->get('title');
            $comment = $request->get('comment');
        }

        $document = new CDocument();
        if ($request->request->has('filetype')) {
            $document->setFiletype($request->get('filetype'));
        }

        $nodeId = (int) $request->get('parentResourceNodeId');
        $document->setParentResourceNode($nodeId);

        switch ($document->getFiletype()) {
            case 'file':
                $content = '';
                if ($request->request->has('contentFile')) {
                    $content = $request->request->get('contentFile');
                }
                $fileParsed = false;
                // File upload.
                if ($request->files->count() > 0) {
                    if (!$request->files->has('uploadFile')) {
                        throw new BadRequestHttpException('"uploadFile" is required');
                    }

                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $request->files->get('uploadFile');
                    $title = $uploadedFile->getClientOriginalName();
                    $document->setUploadFile($uploadedFile);
                    $fileParsed = true;
                }

                // Get data in content and create a HTML file.
                if (!$fileParsed && $content) {
                    $handle = tmpfile();
                    fwrite($handle, $content);
                    $meta = stream_get_meta_data($handle);
                    $file = new UploadedFile($meta['uri'], $title.'.html', 'text/html', null, true);
                    $document->setUploadFile($file);
                    $fileParsed = true;
                }

                if (!$fileParsed) {
                    throw new InvalidArgumentException('filetype was set to "file" but not upload found');
                }

                break;

            case 'folder':
                break;
        }

        if (empty($title)) {
            throw new InvalidArgumentException('title required');
        }

        $document->setTitle($title);

        if ($request->request->has('resourceLinkList')) {
            $links = $request->get('resourceLinkList');
            $links = false === strpos($links, '[') ? json_decode('['.$links.']', true) : json_decode($links, true);
            if (empty($links)) {
                $message = 'resourceLinkList is not a valid json. Use for example: [{"c_id":1, "visibility":1}]';
                throw new InvalidArgumentException($message);
            }
            $document->setResourceLinkArray($links);
        }

        $document->setComment($comment);

        return $document;
    }
}
