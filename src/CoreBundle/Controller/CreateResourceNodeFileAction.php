<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceNodeFileAction
{
    public function __invoke(Request $request): CDocument
    {
        $document = new CDocument();
        $title = $request->get('title');

        if ('file' === $request->get('filetype')) {
            $fileParsed = false;
            // File upload
            if ($request->files->count() > 0) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $request->files->get('uploadFile');
                if (!$uploadedFile) {
                    throw new BadRequestHttpException('"uploadFile" is required');
                }
                $title = $uploadedFile->getClientOriginalName();
                $document->setUploadFile($uploadedFile);
                $fileParsed = true;
            }

            // Get data in content and create a HTML file
            if (false === $fileParsed && $request->request->has('content')) {
                $content = $request->request->get('content');
                $title .= '.html';
                $handle = tmpfile();
                fwrite($handle, $content);
                $meta = stream_get_meta_data($handle);
                $file = new UploadedFile($meta['uri'], $title, 'text/html', null, true);
                $document->setUploadFile($file);
                $fileParsed = true;
            }

            if (false === $fileParsed) {
                throw new \InvalidArgumentException(
                    'filetype was set to "file" but not upload found'
                );
            }
        }

        if ($request->request->has('resourceLinkList')) {
            $links = $request->get('resourceLinkList');
            if (strpos($links, '[') === false) {
                $links = json_decode('['.$links.']', true);
            } else {
                $links = json_decode($links, true);
            }
            if (empty($links)) {
                throw new \InvalidArgumentException(
                    'resourceLinkList is not a valid json. Example: [{"c_id":1:"visibility":1}]'
                );
            }
            $document->setResourceLinkList($links);
        }

        $document->setTitle($title);
        $document->setComment($request->get('comment'));

        $nodeId = (int) $request->get('parentResourceNodeId');
        $document->setParentResourceNode($nodeId);

        return $document;
    }
}
