<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceLink;
use DateTime;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseResourceFileAction
{
    protected function handleCreateRequest(AbstractResource $resource, Request $request): array
    {
        $contentData = $request->getContent();
        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $comment = $contentData['comment'] ?? '';
            $parentResourceNodeId = $contentData['parentResourceNodeId'] ?? 0;
            $fileType = $contentData['filetype'] ?? '';
        } else {
            $title = $request->get('title');
            $comment = $request->get('comment');
            $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
            $fileType = $request->get('filetype');
        }

        if (empty($fileType)) {
            throw new Exception('filetype needed: folder or file');
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('parentResourceNodeId int value needed');
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        switch ($fileType) {
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
                    $resource->setUploadFile($uploadedFile);
                    $fileParsed = true;
                }

                // Get data in content and create a HTML file.
                if (!$fileParsed && $content) {
                    $handle = tmpfile();
                    fwrite($handle, $content);
                    $meta = stream_get_meta_data($handle);
                    $file = new UploadedFile($meta['uri'], $title.'.html', 'text/html', null, true);
                    $resource->setUploadFile($file);
                    $fileParsed = true;
                }

                if (!$fileParsed) {
                    throw new InvalidArgumentException('filetype was set to "file" but not upload file found');
                }

                break;
            case 'folder':
                break;
        }

        if (empty($title)) {
            throw new InvalidArgumentException('title required');
        }

        $resource->setResourceName($title);

        return [
            'title' => $title,
            'comment' => $comment,
            'parentResourceNodeId' => $parentResourceNodeId,
            'filetype' => $fileType,
        ];
    }

    protected function handleUpdateRequest(AbstractResource $resource, $repo, Request $request)
    {
        //error_log('handleUpdateRequest');
        $contentData = $request->getContent();
        $resourceLinkList = [];
        if (!empty($contentData)) {
            //error_log('contentData');
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'];
            $content = $contentData['contentFile'];
            //$comment = $contentData['comment'] ?? '';
            $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');
            //$comment = $request->request->get('comment');
        }

        $repo->setResourceName($resource, $title);

        $hasFile = $resource->getResourceNode()->hasResourceFile();

        //if ('file' === $fileType && !empty($content)) {
        if ($hasFile && !empty($content)) {
            $resourceNode = $resource->getResourceNode();
            if ($resourceNode->hasResourceFile()) {
                $resourceNode->setContent($content);
                $resourceNode->getResourceFile()->setSize(\strlen($content));
            }
            $resourceNode->setUpdatedAt(new DateTime());
            $resourceNode->getResourceFile()->setUpdatedAt(new DateTime());
            $resource->setResourceNode($resourceNode);
        }

        $link = null;
        if (!empty($resourceLinkList)) {
            foreach ($resourceLinkList as $linkArray) {
                // Find the exact link.
                $linkId = $linkArray['id'];
                /** @var ResourceLink $link */
                $link = $resource->getResourceNode()->getResourceLinks()
                    ->filter(
                        fn ($link) => $link->getId() === $linkId
                    )->first();

                if (null !== $link) {
                    $link->setVisibility((int) $linkArray['visibility']);

                    break;
                }
            }
        }

        //$isRecursive = 'folder' === $fileType;
        $isRecursive = !$hasFile;
        // If it's a folder then change the visibility to the children (That have the same link).
        if ($isRecursive && null !== $link) {
            $repo->copyVisibilityToChildren($resource->getResourceNode(), $link);
        }

        //$document->setComment($comment);

        error_log('Finish update resource node file action');

        return $resource;
    }
}
