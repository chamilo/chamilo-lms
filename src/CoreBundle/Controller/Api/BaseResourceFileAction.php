<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Utils\CreateUploadedFile;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class BaseResourceFileAction
{
    public static function setLinks(AbstractResource $resource, EntityManagerInterface $em): void
    {
        $resourceNode = $resource->getResourceNode();
        $links = $resource->getResourceLinkArray();
        if ($links) {
            $groupRepo = $em->getRepository(CGroup::class);
            $courseRepo = $em->getRepository(Course::class);
            $sessionRepo = $em->getRepository(Session::class);
            $userRepo = $em->getRepository(User::class);

            foreach ($links as $link) {
                $resourceLink = new ResourceLink();
                $linkSet = false;
                if (isset($link['cid']) && !empty($link['cid'])) {
                    $course = $courseRepo->find($link['cid']);
                    if (null !== $course) {
                        $linkSet = true;
                        $resourceLink->setCourse($course);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Course #%s does not exists', $link['cid']));
                    }
                }

                if (isset($link['sid']) && !empty($link['sid'])) {
                    $session = $sessionRepo->find($link['sid']);
                    if (null !== $session) {
                        $linkSet = true;
                        $resourceLink->setSession($session);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Session #%s does not exists', $link['sid']));
                    }
                }

                if (isset($link['gid']) && !empty($link['gid'])) {
                    $group = $groupRepo->find($link['gid']);
                    if (null !== $group) {
                        $linkSet = true;
                        $resourceLink->setGroup($group);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Group #%s does not exists', $link['gid']));
                    }
                }

                if (isset($link['uid']) && !empty($link['uid'])) {
                    $user = $userRepo->find($link['uid']);
                    if (null !== $user) {
                        $linkSet = true;
                        $resourceLink->setUser($user);
                    } else {
                        throw new InvalidArgumentException(\sprintf('User #%s does not exists', $link['uid']));
                    }
                }

                if (isset($link['visibility'])) {
                    $resourceLink->setVisibility((int) $link['visibility']);
                } else {
                    throw new InvalidArgumentException('Link needs a visibility key');
                }

                if ($linkSet) {
                    $em->persist($resourceLink);
                    $resourceNode->addResourceLink($resourceLink);
                    // $em->persist($resourceNode);
                    // $em->persist($resource->getResourceNode());
                }
            }
        }

        // Use by Chamilo not api platform.
        $links = $resource->getResourceLinkEntityList();
        if ($links) {
            // error_log('$resource->getResourceLinkEntityList()');
            foreach ($links as $link) {
                /*$rights = [];
                 * switch ($link->getVisibility()) {
                 * case ResourceLink::VISIBILITY_PENDING:
                 * case ResourceLink::VISIBILITY_DRAFT:
                 * $editorMask = ResourceNodeVoter::getEditorMask();
                 * $resourceRight = new ResourceRight();
                 * $resourceRight
                 * ->setMask($editorMask)
                 * ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                 * ;
                 * $rights[] = $resourceRight;
                 * break;
                 * }
                 * if (!empty($rights)) {
                 * foreach ($rights as $right) {
                 * $link->addResourceRight($right);
                 * }
                 * }*/
                // error_log('link adding to node: '.$resource->getResourceNode()->getId());
                // error_log('link with user : '.$link->getUser()->getUsername());
                $resource->getResourceNode()->addResourceLink($link);

                $em->persist($link);
            }
        }
    }

    /**
     * @todo use this function inside handleCreateFileRequest
     */
    protected function handleCreateRequest(AbstractResource $resource, ResourceRepository $resourceRepository, Request $request): array
    {
        $contentData = $request->getContent();

        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $parentResourceNodeId = (int) ($contentData['parentResourceNodeId'] ?? 0);
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
            if (empty($resourceLinkList)) {
                $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
            }
        } else {
            $contentData = $request->request->all();
            $title = $request->get('title');
            $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
            $resourceLinkList = $request->get('resourceLinkList', []);
            if (!empty($resourceLinkList)) {
                $resourceLinkList = !str_contains($resourceLinkList, '[') ? json_decode('['.$resourceLinkList.']', true) : json_decode($resourceLinkList, true);
                if (empty($resourceLinkList)) {
                    $message = 'resourceLinkList is not a valid json. Use for example: [{"cid":1, "visibility":1}]';

                    throw new InvalidArgumentException($message);
                }
            }
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('Parameter parentResourceNodeId int value is needed');
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        if (empty($title)) {
            throw new InvalidArgumentException('title is required');
        }

        $resource->setResourceName($title);

        // Set resource link list if exists.
        if (!empty($resourceLinkList)) {
            $resource->setResourceLinkArray($resourceLinkList);
        }

        return $contentData;
    }

    /**
     * Handles the creation logic for a student publication comment resource.
     */
    public function handleCreateCommentRequest(
        AbstractResource $resource,
        ResourceRepository $resourceRepository,
        Request $request,
        EntityManager $em,
        string $fileExistsOption = '',
        ?TranslatorInterface $translator = null
    ): array {
        $title = $request->get('comment', '');
        $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
        $fileType = $request->get('filetype');
        $uploadedFile = null;

        if (empty($fileType)) {
            throw new Exception('filetype needed: folder or file');
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('parentResourceNodeId int value needed');
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        if ($request->files->count() > 0 && $request->files->has('uploadFile')) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('uploadFile');
            $resource->setUploadFile($uploadedFile);
        }

        return [
            'title' => $title,
            'filename' => $uploadedFile?->getClientOriginalName(),
            'filetype' => $fileType,
        ];
    }

    /**
     * Function loaded when creating a resource using the api, then the ResourceListener is executed.
     */
    public function handleCreateFileRequest(
        AbstractResource $resource,
        ResourceRepository $resourceRepository,
        Request $request,
        EntityManager $em,
        string $fileExistsOption = '',
        ?TranslatorInterface $translator = null
    ): array {
        $contentData = $request->getContent();

        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $comment = $contentData['comment'] ?? '';
            $parentResourceNodeId = (int) ($contentData['parentResourceNodeId'] ?? 0);
            $fileType = $contentData['filetype'] ?? '';
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
        } else {
            $title = $request->get('title');
            $comment = $request->get('comment');
            $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
            $fileType = $request->get('filetype');
            $resourceLinkList = $request->get('resourceLinkList', []);
            if (!empty($resourceLinkList)) {
                $resourceLinkList = !str_contains($resourceLinkList, '[') ? json_decode('['.$resourceLinkList.']', true) : json_decode($resourceLinkList, true);
                if (empty($resourceLinkList)) {
                    $message = 'resourceLinkList is not a valid json. Use for example: [{"cid":1, "visibility":1}]';

                    throw new InvalidArgumentException($message);
                }
            }
        }

        if (empty($fileType)) {
            throw new Exception('filetype needed: folder or file');
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('parentResourceNodeId int value needed');
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        switch ($fileType) {
            case 'certificate':
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

                    if (empty($title)) {
                        throw new InvalidArgumentException('title is required');
                    }

                    // Handle the appropriate action based on the fileExistsOption
                    if (!empty($fileExistsOption)) {
                        // Check if a document with the same title and parent resource node already exists
                        $existingDocument = $resourceRepository->findByTitleAndParentResourceNode($title, $parentResourceNodeId);
                        if ($existingDocument) {
                            if ('overwrite' === $fileExistsOption) {
                                $existingDocument->setTitle($title);
                                $existingDocument->setComment($comment);

                                $resourceNode = $existingDocument->getResourceNode();

                                $resourceFile = $resourceNode->getFirstResourceFile();
                                if ($resourceFile instanceof ResourceFile) {
                                    $resourceFile->setFile($uploadedFile);
                                    $em->persist($resourceFile);
                                } else {
                                    $existingDocument->setUploadFile($uploadedFile);
                                }

                                $resourceNode->setUpdatedAt(new DateTime());
                                $existingDocument->setResourceNode($resourceNode);

                                $em->persist($existingDocument);
                                $em->flush();

                                // Return any data you need for further processing
                                return [
                                    'title' => $title,
                                    'filetype' => 'file',
                                    'comment' => $comment,
                                ];
                            }

                            if ('rename' == $fileExistsOption) {
                                // Perform actions when file exists and 'rename' option is selected
                                $newTitle = $this->generateUniqueTitle($title); // Generate a unique title
                                $resource->setResourceName($newTitle);
                                $resource->setUploadFile($uploadedFile);
                                if (!empty($resourceLinkList)) {
                                    $resource->setResourceLinkArray($resourceLinkList);
                                }
                                $em->persist($resource);
                                $em->flush();

                                // Return any data you need for further processing
                                return [
                                    'title' => $newTitle,
                                    'filetype' => 'file',
                                    'comment' => $comment,
                                ];
                            }

                            if ('nothing' == $fileExistsOption) {
                                // Perform actions when file exists and 'nothing' option is selected
                                // Display a message indicating that the file already exists
                                // or perform any other desired actions based on your application's requirements
                                $resource->setResourceName($title);
                                $flashBag = $request->getSession()->getFlashBag();
                                $message = $translator ? $translator->trans('upload.already_exists') : 'Upload Already Exists';
                                $flashBag->add('warning', $message);

                                throw new BadRequestHttpException($translator ? $translator->trans('file.already_exists') : 'The file already exists and was not uploaded.');
                            }

                            throw new InvalidArgumentException('Invalid fileExistsOption');
                        } else {
                            $resource->setResourceName($title);
                            $resource->setUploadFile($uploadedFile);
                            $fileParsed = true;
                        }
                    }
                }

                // Get data in content and create a HTML file.
                if (!$fileParsed && $content) {
                    $uploadedFile = CreateUploadedFile::fromString($title.'.html', 'text/html', $content);
                    $resource->setUploadFile($uploadedFile);
                    $fileParsed = true;
                }

                if (!$fileParsed) {
                    throw new InvalidArgumentException('filetype was set to "file" but no upload file found');
                }

                break;

            case 'folder':
                break;
        }

        // Set resource link list if exists.
        if (!empty($resourceLinkList)) {
            $resource->setResourceLinkArray($resourceLinkList);
        }

        return [
            'title' => $title,
            'filetype' => $fileType,
            'comment' => $comment,
        ];
    }

    protected function handleCreateFileRequestUncompress(AbstractResource $resource, Request $request, EntityManager $em, KernelInterface $kernel): array
    {
        // Get the parameters from the request
        $parentResourceNodeId = (int) $request->get('parentResourceNodeId');
        $fileType = $request->get('filetype');
        $resourceLinkList = $request->get('resourceLinkList', []);
        if (!empty($resourceLinkList)) {
            $resourceLinkList = !str_contains($resourceLinkList, '[') ? json_decode('['.$resourceLinkList.']', true) : json_decode($resourceLinkList, true);
            if (empty($resourceLinkList)) {
                $message = 'resourceLinkList is not a valid json. Use for example: [{"cid":1, "visibility":1}]';

                throw new InvalidArgumentException($message);
            }
        }

        if (empty($fileType)) {
            throw new Exception('filetype needed: folder or file');
        }

        if (0 === $parentResourceNodeId) {
            throw new Exception('parentResourceNodeId int value needed');
        }

        if ('file' == $fileType && $request->files->count() > 0) {
            if (!$request->files->has('uploadFile')) {
                throw new BadRequestHttpException('"uploadFile" is required');
            }

            $uploadedFile = $request->files->get('uploadFile');
            $resourceTitle = $uploadedFile->getClientOriginalName();
            $resource->setResourceName($resourceTitle);
            $resource->setUploadFile($uploadedFile);

            if ('zip' === $uploadedFile->getClientOriginalExtension()) {
                // Extract the files and subdirectories
                $extractedData = $this->extractZipFile($uploadedFile, $kernel);
                $folderStructure = $extractedData['folderStructure'];
                $extractPath = $extractedData['extractPath'];
                $documents = $this->saveZipContentsAsDocuments($folderStructure, $em, $resourceLinkList, $parentResourceNodeId, '', $extractPath, $processedItems);
            }
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        return [
            'filetype' => $fileType,
            'comment' => 'Uncompressed',
        ];
    }

    protected function handleUpdateRequest(AbstractResource $resource, ResourceRepository $repo, Request $request, EntityManager $em): AbstractResource
    {
        $contentData = $request->getContent();
        $resourceLinkList = [];
        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            if (isset($contentData['parentResourceNodeId']) && 1 === \count($contentData)) {
                $parentResourceNodeId = (int) $contentData['parentResourceNodeId'];
            }
            $title = $contentData['title'] ?? '';
            $content = $contentData['contentFile'] ?? '';
            $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');
        }

        $repo->setResourceName($resource, $title);

        $resourceNode = $resource->getResourceNode();
        $hasFile = $resourceNode->hasResourceFile();

        if ($hasFile && !empty($content)) {
            // The content is updated by the ResourceNodeListener.php
            $resourceNode->setContent($content);
            foreach ($resourceNode->getResourceFiles() as $resourceFile) {
                $resourceFile->setSize(\strlen($content));
            }
            $resource->setResourceNode($resourceNode);
        }

        $link = null;
        if (!empty($resourceLinkList)) {
            foreach ($resourceLinkList as $key => &$linkArray) {
                // Find the exact link.
                $linkId = $linkArray['id'] ?? 0;
                if (!empty($linkId)) {
                    /** @var ResourceLink $link */
                    $link = $resourceNode->getResourceLinks()->filter(fn ($link) => $link->getId() === $linkId)->first();

                    if (null !== $link) {
                        $link->setVisibility((int) $linkArray['visibility']);
                        unset($resourceLinkList[$key]);

                        $em->persist($link);
                    }
                }
            }

            $resource->setResourceLinkArray($resourceLinkList);
            self::setLinks($resource, $em);
        }

        $isRecursive = !$hasFile;
        // If it's a folder then change the visibility to the children (That have the same link).
        if ($isRecursive && null !== $link) {
            $repo->copyVisibilityToChildren($resource->getResourceNode(), $link);
        }

        if (!empty($parentResourceNodeId)) {
            $parentResourceNode = $em->getRepository(ResourceNode::class)->find($parentResourceNodeId);
            if ($parentResourceNode) {
                $resourceNode->setParent($parentResourceNode);
            }
        }

        $resourceNode->setUpdatedAt(new DateTime());

        return $resource;
    }

    private function saveZipContentsAsDocuments(array $folderStructure, EntityManager $em, $resourceLinkList = [], $parentResourceId = null, $currentPath = '', $extractPath = '', &$processedItems = []): array
    {
        $documents = [];

        foreach ($folderStructure as $key => $item) {
            if (\is_array($item)) {
                $folderName = $key;
                $subFolderStructure = $item;

                $document = new CDocument();
                $document->setTitle($folderName);
                $document->setFiletype('folder');

                if (null !== $parentResourceId) {
                    $document->setParentResourceNode($parentResourceId);
                }

                if (!empty($resourceLinkList)) {
                    $document->setResourceLinkArray($resourceLinkList);
                }

                $em->persist($document);
                $em->flush();

                $documentId = $document->getResourceNode()->getId();
                $documents[$documentId] = [
                    'name' => $document->getTitle(),
                    'files' => [],
                ];

                $subDocuments = $this->saveZipContentsAsDocuments($subFolderStructure, $em, $resourceLinkList, $documentId, $currentPath.$folderName.'/', $extractPath, $processedItems);
                $documents[$documentId]['files'] = $subDocuments;
            } else {
                $fileName = $item;

                $document = new CDocument();
                $document->setTitle($fileName);
                $document->setFiletype('file');

                if (null !== $parentResourceId) {
                    $document->setParentResourceNode($parentResourceId);
                }

                if (!empty($resourceLinkList)) {
                    $document->setResourceLinkArray($resourceLinkList);
                }

                $filePath = $extractPath.'/'.$currentPath.$fileName;

                if (file_exists($filePath)) {
                    $uploadedFile = new UploadedFile(
                        $filePath,
                        $fileName
                    );

                    $document->setUploadFile($uploadedFile);
                    $em->persist($document);
                    $em->flush();

                    $documentId = $document->getResourceNode()->getId();
                    $documents[$documentId] = [
                        'name' => $document->getTitle(),
                        'files' => [],
                    ];
                } else {
                    error_log('File does not exist: '.$filePath);

                    continue;
                }
            }
        }

        return $documents;
    }

    private function extractZipFile(UploadedFile $file, KernelInterface $kernel): array
    {
        // Get the temporary path of the ZIP file
        $zipFilePath = $file->getRealPath();

        // Create an instance of the ZipArchive class
        $zip = new ZipArchive();
        $zip->open($zipFilePath);

        $cacheDirectory = $kernel->getCacheDir();
        $extractPath = $cacheDirectory.'/'.uniqid('extracted_', true);
        mkdir($extractPath);

        // Extract the contents of the ZIP file
        $zip->extractTo($extractPath);

        // Array to store the sorted extracted paths
        $extractedPaths = [];

        // Iterate over each file or directory in the ZIP file
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $extractedPaths[] = $extractPath.'/'.$filename;
        }

        // Close the ZIP file
        $zip->close();

        // Build the folder structure and file associations
        $folderStructure = $this->buildFolderStructure($extractedPaths, $extractPath);

        // Return the array of folder structure and the extraction path
        return [
            'folderStructure' => $folderStructure,
            'extractPath' => $extractPath,
        ];
    }

    private function buildFolderStructure(array $paths, string $extractPath): array
    {
        $folderStructure = [];

        foreach ($paths as $path) {
            $relativePath = str_replace($extractPath.'/', '', $path);
            $parts = explode('/', $relativePath);

            $currentLevel = &$folderStructure;

            foreach ($parts as $part) {
                if (!isset($currentLevel[$part])) {
                    $currentLevel[$part] = [];
                }

                $currentLevel = &$currentLevel[$part];
            }
        }

        return $this->formatFolderStructure($folderStructure);
    }

    private function formatFolderStructure(array $folderStructure): array
    {
        $result = [];

        foreach ($folderStructure as $folder => $contents) {
            $formattedContents = $this->formatFolderStructure($contents);

            if (!empty($formattedContents)) {
                $result[$folder] = $formattedContents;
            } elseif (!empty($folder)) {
                $result[] = $folder;
            }
        }

        return $result;
    }

    /**
     * Generates a unique filename by appending a random suffix.
     */
    private function generateUniqueTitle(string $title): string
    {
        $info = pathinfo($title);
        $filename = $info['filename'];
        $extension = isset($info['extension']) ? '.'.$info['extension'] : '';

        return $filename.'_'.uniqid().$extension;
    }
}
