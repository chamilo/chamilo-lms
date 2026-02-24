<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
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
use Throwable;
use ZipArchive;

class BaseResourceFileAction
{
    public static function setLinks(AbstractResource $resource, EntityManagerInterface $em): void
    {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            // Nothing to do if there is no resource node.
            return;
        }

        /** @var ResourceNode|null $parentNode */
        $parentNode = $resourceNode->getParent();

        /** @var ResourceLinkRepository $resourceLinkRepo */
        $resourceLinkRepo = $em->getRepository(ResourceLink::class);

        $links = $resource->getResourceLinkArray();
        if ($links) {
            $groupRepo = $em->getRepository(CGroup::class);
            $courseRepo = $em->getRepository(Course::class);
            $sessionRepo = $em->getRepository(Session::class);
            $userRepo = $em->getRepository(User::class);

            foreach ($links as $link) {
                $resourceLink = new ResourceLink();
                $linkSet = false;

                $course = null;
                $session = null;
                $group = null;
                $user = null;

                if (isset($link['cid']) && !empty($link['cid'])) {
                    $course = $courseRepo->find($link['cid']);
                    if (null !== $course) {
                        $linkSet = true;
                        $resourceLink->setCourse($course);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Course #%s does not exist', $link['cid']));
                    }
                }

                if (isset($link['sid']) && !empty($link['sid'])) {
                    $session = $sessionRepo->find($link['sid']);
                    if (null !== $session) {
                        $linkSet = true;
                        $resourceLink->setSession($session);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Session #%s does not exist', $link['sid']));
                    }
                }

                if (isset($link['gid']) && !empty($link['gid'])) {
                    $group = $groupRepo->find($link['gid']);
                    if (null !== $group) {
                        $linkSet = true;
                        $resourceLink->setGroup($group);
                    } else {
                        throw new InvalidArgumentException(\sprintf('Group #%s does not exist', $link['gid']));
                    }
                }

                if (isset($link['uid']) && !empty($link['uid'])) {
                    $user = $userRepo->find($link['uid']);
                    if (null !== $user) {
                        $linkSet = true;
                        $resourceLink->setUser($user);
                    } else {
                        throw new InvalidArgumentException(\sprintf('User #%s does not exist', $link['uid']));
                    }
                }

                if (isset($link['visibility'])) {
                    $resourceLink->setVisibility((int) $link['visibility']);
                } else {
                    throw new InvalidArgumentException('Link needs a visibility key');
                }

                if ($linkSet) {
                    // Attach the node to the link.
                    $resourceLink->setResourceNode($resourceNode);

                    // If the resource has a parent node, try to resolve the parent link
                    // in the same context so we can maintain a context-aware hierarchy.
                    if ($parentNode instanceof ResourceNode) {
                        $parentLink = $resourceLinkRepo->findParentLinkForContext(
                            $parentNode,
                            $course,
                            $session,
                            $group,
                            null,
                            $user
                        );

                        if (null !== $parentLink) {
                            $resourceLink->setParent($parentLink);
                        }
                    }

                    $em->persist($resourceLink);
                    $resourceNode->addResourceLink($resourceLink);
                }
            }
        }

        // Use by Chamilo not api platform.
        $links = $resource->getResourceLinkEntityList();
        if ($links) {
            foreach ($links as $link) {
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
            $rawParent = $contentData['parentResourceNodeId'] ?? 0;
            $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
            if (empty($resourceLinkList)) {
                $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
            }
        } else {
            $contentData = $request->request->all();
            $title = $request->get('title');
            $rawParent = $request->get('parentResourceNodeId');
            $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
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

    public function handleCreateCommentRequest(
        AbstractResource $resource,
        ResourceRepository $resourceRepository,
        Request $request,
        EntityManager $em,
        string $fileExistsOption = '',
        ?TranslatorInterface $translator = null
    ): array {
        $title = $request->get('comment', '');
        $rawParent = $request->get('parentResourceNodeId');
        $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
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

    public function handleCreateFileRequest(
        AbstractResource $resource,
        ResourceRepository $resourceRepository,
        Request $request,
        EntityManager $em,
        string $fileExistsOption = '',
        ?TranslatorInterface $translator = null,
        ?CourseRepository $courseRepository = null,
        ?CourseHelper $courseHelper = null
    ): array {
        $contentData = $request->getContent();

        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'] ?? '';
            $comment = $contentData['comment'] ?? '';
            $rawParent = $contentData['parentResourceNodeId'] ?? 0;
            $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
            $fileType = $contentData['filetype'] ?? '';
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
        } else {
            $title = $request->get('title');
            $comment = $request->get('comment');
            $rawParent = $request->get('parentResourceNodeId');
            $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
            $fileType = $request->get('filetype');
            $resourceLinkList = $request->get('resourceLinkList', []);
            if (!empty($resourceLinkList)) {
                $resourceLinkList = !str_contains($resourceLinkList, '[')
                    ? json_decode('['.$resourceLinkList.']', true)
                    : json_decode($resourceLinkList, true);

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

        $uploadedFile = null;

        switch ($fileType) {
            case 'certificate':
            case 'file':
                $content = '';
                if ($request->request->has('contentFile')) {
                    $content = (string) $request->request->get('contentFile');
                }

                $fileParsed = false;

                // Multipart upload
                if ($request->files->count() > 0) {
                    if (!$request->files->has('uploadFile')) {
                        throw new BadRequestHttpException('"uploadFile" is required');
                    }

                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $request->files->get('uploadFile');

                    $title = (string) $uploadedFile->getClientOriginalName();
                    if (empty($title)) {
                        throw new InvalidArgumentException('title is required');
                    }

                    // Handle overwrite/rename/nothing when same title already exists under parent
                    if (!empty($fileExistsOption)) {
                        $existingDocument = $resourceRepository->findByTitleAndParentResourceNode($title, $parentResourceNodeId);
                        if ($existingDocument) {
                            if ('overwrite' === $fileExistsOption) {
                                // Quota check with delta: new - old
                                $oldBytes = 0;
                                $existingNode = $existingDocument->getResourceNode();
                                $existingFile = $existingNode?->getFirstResourceFile();
                                if ($existingFile instanceof ResourceFile) {
                                    $oldBytes = (int) ($existingFile->getSize() ?? 0);
                                }

                                $newBytes = (int) ($uploadedFile->getSize() ?? 0);
                                $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $newBytes, $oldBytes);
                                $existingDocument->setTitle($title);
                                $existingDocument->setComment($comment);
                                $existingDocument->setFiletype($fileType);

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

                                return [
                                    'title' => $title,
                                    'filetype' => $fileType,
                                    'comment' => $comment,
                                ];
                            }

                            if ('rename' === $fileExistsOption) {
                                $newBytes = (int) ($uploadedFile->getSize() ?? 0);
                                $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $newBytes, 0);

                                $newTitle = $this->generateUniqueTitle($title);
                                $resource->setResourceName($newTitle);
                                $resource->setUploadFile($uploadedFile);
                                if (!empty($resourceLinkList)) {
                                    $resource->setResourceLinkArray($resourceLinkList);
                                }
                                $em->persist($resource);
                                $em->flush();

                                return [
                                    'title' => $newTitle,
                                    'filetype' => $fileType,
                                    'comment' => $comment,
                                ];
                            }

                            if ('nothing' === $fileExistsOption) {
                                $resource->setResourceName($title);
                                $message = $translator
                                    ? $translator->trans('The operation is impossible, a file with this name already exists.')
                                    : 'The operation is impossible, a file with this name already exists.';

                                throw new BadRequestHttpException($message);
                            }

                            throw new InvalidArgumentException('Invalid fileExistsOption');
                        }

                        // No existing doc with same name -> create new
                        $newBytes = (int) ($uploadedFile->getSize() ?? 0);
                        $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $newBytes, 0);
                        $resource->setResourceName($title);
                        $resource->setUploadFile($uploadedFile);
                        $fileParsed = true;
                    } else {
                        // No fileExistsOption specified -> still apply quota check for new creation
                        $newBytes = (int) ($uploadedFile->getSize() ?? 0);
                        $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $newBytes, 0);

                        $resource->setResourceName($title);
                        $resource->setUploadFile($uploadedFile);
                        $fileParsed = true;
                    }
                }

                // HTML contentFile => create an UploadedFile from content
                if (!$fileParsed && !empty($content)) {
                    $newBytes = (int) \strlen((string) $content);
                    $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $newBytes, 0);

                    $uploadedFile = CreateUploadedFileHelper::fromString($title.'.html', 'text/html', $content);
                    $resource->setUploadFile($uploadedFile);
                    $fileParsed = true;
                }

                if (!$fileParsed) {
                    throw new InvalidArgumentException('filetype was set to "file" but no upload file found');
                }

                break;

            case 'folder':
                // No quota check for folders
                break;
        }

        if (!empty($resourceLinkList)) {
            $resource->setResourceLinkArray($resourceLinkList);
        }

        $filetypeResult = $fileType;

        // Auto-detect video for better UI/filters
        if ($uploadedFile instanceof UploadedFile) {
            $mimeType = (string) $uploadedFile->getMimeType();
            if (str_starts_with($mimeType, 'video/')) {
                $filetypeResult = 'video';
                $comment = trim((string) $comment.' [video]');
            }
        }

        return [
            'title' => (string) $title,
            'filetype' => (string) $filetypeResult,
            'comment' => (string) $comment,
        ];
    }

    protected function handleCreateFileRequestUncompress(
        AbstractResource $resource,
        Request $request,
        EntityManager $em,
        KernelInterface $kernel,
        ?CourseRepository $courseRepository = null,
        ?CDocumentRepository $documentRepository = null,
        ?CourseHelper $courseHelper = null
    ): array {
        $rawParent = $request->get('parentResourceNodeId');
        $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);

        $fileType = $request->get('filetype');
        $resourceLinkList = $request->get('resourceLinkList', []);
        if (!empty($resourceLinkList)) {
            if (\is_string($resourceLinkList)) {
                $resourceLinkList = !str_contains($resourceLinkList, '[')
                    ? json_decode('['.$resourceLinkList.']', true)
                    : json_decode($resourceLinkList, true);
            }

            if (empty($resourceLinkList) || !\is_array($resourceLinkList)) {
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

        if ('file' === $fileType && $request->files->count() > 0) {
            if (!$request->files->has('uploadFile')) {
                throw new BadRequestHttpException('"uploadFile" is required');
            }

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('uploadFile');
            $resourceTitle = (string) $uploadedFile->getClientOriginalName();
            $resource->setResourceName($resourceTitle);
            $resource->setUploadFile($uploadedFile);

            if ('zip' === strtolower((string) $uploadedFile->getClientOriginalExtension())) {
                $zipBytes = $this->getZipTotalUncompressedSize($uploadedFile);
                $this->assertQuotaForRequest($courseHelper, $em, $parentResourceNodeId, $resourceLinkList, $zipBytes, 0);
                $extractedData = $this->extractZipFile($uploadedFile, $kernel);
                $folderStructure = $extractedData['folderStructure'];
                $extractPath = $extractedData['extractPath'];

                $processedItems = [];
                $this->saveZipContentsAsDocuments(
                    $folderStructure,
                    $em,
                    $resourceLinkList,
                    $parentResourceNodeId,
                    '',
                    $extractPath,
                    $processedItems
                );
            }
        }

        $resource->setParentResourceNode($parentResourceNodeId);

        return [
            'title' => (string) $resource->getResourceName(),
            'filetype' => (string) $fileType,
            'comment' => 'Uncompressed',
        ];
    }

    protected function handleUpdateRequest(AbstractResource $resource, ResourceRepository $repo, Request $request, EntityManager $em): AbstractResource
    {
        $contentData = $request->getContent();
        $resourceLinkList = [];
        $parentResourceNodeId = 0;
        $title = null;
        $content = null;

        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);

            if (isset($contentData['parentResourceNodeId'])) {
                $parentResourceNodeId = (int) ($this->normalizeNodeId($contentData['parentResourceNodeId']) ?? 0);
            }

            $title = $contentData['title'] ?? null;
            $content = $contentData['contentFile'] ?? null;
            $resourceLinkList = $contentData['resourceLinkListFromEntity'] ?? [];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');

            // Keep compatibility with form requests
            if ($request->query->has('parentResourceNodeId') || $request->request->has('parentResourceNodeId')) {
                $rawParent = $request->get('parentResourceNodeId');
                $parentResourceNodeId = (int) ($this->normalizeNodeId($rawParent) ?? 0);
            }
        }

        // Only update the name when a title is explicitly provided.
        if (null !== $title) {
            $repo->setResourceName($resource, $title);
        }

        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return $resource;
        }

        $hasFile = $resourceNode->hasResourceFile();

        if ($hasFile && !empty($content)) {
            $resourceNode->setContent($content);
            foreach ($resourceNode->getResourceFiles() as $resourceFile) {
                $resourceFile->setSize(\strlen($content));
            }
            $resource->setResourceNode($resourceNode);
        }

        $link = null;
        if (!empty($resourceLinkList)) {
            foreach ($resourceLinkList as $key => &$linkArray) {
                $linkId = $linkArray['id'] ?? 0;
                if (!empty($linkId)) {
                    $candidate = $resourceNode->getResourceLinks()->filter(
                        static fn ($l) => $l instanceof ResourceLink && $l->getId() === $linkId
                    )->first();

                    if ($candidate instanceof ResourceLink) {
                        $link = $candidate;
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
        if ($isRecursive && $link instanceof ResourceLink) {
            $repo->copyVisibilityToChildren($resource->getResourceNode(), $link);
        }

        if ($parentResourceNodeId > 0) {
            $parentResourceNode = $em->getRepository(ResourceNode::class)->find($parentResourceNodeId);

            if ($parentResourceNode instanceof ResourceNode) {
                $resourceNode->setParent($parentResourceNode);
            }

            if ($resource instanceof CDocument) {
                /** @var ResourceLinkRepository $linkRepo */
                $linkRepo = $em->getRepository(ResourceLink::class);

                $course = null;
                $session = null;
                $group = null;
                $usergroup = null;
                $user = null;

                $courseId = $request->query->getInt('cid', 0);
                $sessionId = $request->query->getInt('sid', 0);
                $groupId = $request->query->getInt('gid', 0);
                $userId = $request->query->getInt('uid', 0);
                $usergroupId = $request->query->getInt('ugid', 0);

                if ($courseId > 0) {
                    $course = $em->getRepository(Course::class)->find($courseId);
                }

                if ($sessionId > 0) {
                    $session = $em->getRepository(Session::class)->find($sessionId);
                }

                if ($groupId > 0) {
                    $group = $em->getRepository(CGroup::class)->find($groupId);
                }

                if ($userId > 0) {
                    $user = $em->getRepository(User::class)->find($userId);
                }

                if ($usergroupId > 0) {
                    $usergroup = $em->getRepository(Usergroup::class)->find($usergroupId);
                }

                $parentLink = null;
                if ($parentResourceNode instanceof ResourceNode) {
                    $parentLink = $linkRepo->findParentLinkForContext(
                        $parentResourceNode,
                        $course,
                        $session,
                        $group,
                        $usergroup,
                        $user
                    );
                }

                $currentLink = $linkRepo->findLinkForResourceInContext(
                    $resource,
                    $course,
                    $session,
                    $group,
                    $usergroup,
                    $user
                );

                if (null !== $currentLink) {
                    $currentLink->setParent($parentLink);
                    $em->persist($currentLink);
                }
            }
        }

        $resourceNode->setUpdatedAt(new DateTime());

        return $resource;
    }

    private function normalizeNodeId(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $value = trim($value);

            if ('' === $value) {
                return null;
            }

            if (ctype_digit($value)) {
                return (int) $value;
            }

            if (preg_match('#/api/resource_nodes/(\d+)#', $value, $m)) {
                return (int) $m[1];
            }
        }

        if (null === $value) {
            return null;
        }

        // Last resort: if it is numeric-like
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
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
                    $mime = @mime_content_type($filePath) ?: null;

                    $uploadedFile = new UploadedFile(
                        $filePath,
                        $fileName,
                        $mime,
                        null,
                        true
                    );

                    $mimeType = $uploadedFile->getMimeType();
                    if (str_starts_with($mimeType, 'video/')) {
                        $document->setFiletype('video');
                        $document->setComment('[video]');
                    } else {
                        $document->setFiletype('file');
                    }

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

    private function getZipTotalUncompressedSize(UploadedFile $zipFile): int
    {
        $path = $zipFile->getRealPath();
        if (!$path) {
            return 0;
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            return 0;
        }

        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!\is_array($stat)) {
                continue;
            }

            $name = (string) ($stat['name'] ?? '');
            // Skip directories
            if ('' !== $name && str_ends_with($name, '/')) {
                continue;
            }

            $size = (int) ($stat['size'] ?? 0);
            if ($size > 0) {
                $total += $size;
            }
        }

        $zip->close();

        return $total;
    }

    private function assertQuotaForRequest(
        ?CourseHelper $courseHelper,
        EntityManagerInterface $em,
        int $parentResourceNodeId,
        array $resourceLinkList,
        int $bytesToAdd,
        int $bytesToSubtract = 0
    ): void {
        if (!$courseHelper instanceof CourseHelper) {
            return;
        }

        if ($bytesToAdd <= 0) {
            return;
        }

        $deltaBytes = $bytesToAdd - max(0, $bytesToSubtract);
        if ($deltaBytes <= 0) {
            return;
        }

        $courses = $this->resolveCoursesForQuotaCheck($em, $parentResourceNodeId, $resourceLinkList);
        if (empty($courses)) {
            return;
        }

        foreach ($courses as $course) {
            try {
                $courseHelper->assertCanStoreDocumentBytes($course, $deltaBytes);
            } catch (Throwable $e) {
                throw new BadRequestHttpException(\sprintf('Not enough space in course #%d.', (int) $course->getId()));
            }
        }
    }

    private function resolveCoursesForQuotaCheck(
        EntityManagerInterface $em,
        int $parentResourceNodeId,
        array $resourceLinkList
    ): array {
        $courses = [];
        $courseRepo = $em->getRepository(Course::class);

        foreach ($resourceLinkList as $link) {
            $cid = (int) ($link['cid'] ?? 0);
            if ($cid > 0) {
                $course = $courseRepo->find($cid);
                if ($course instanceof Course) {
                    $courses[(int) $course->getId()] = $course;
                }
            }
        }

        if (empty($courses) && $parentResourceNodeId > 0) {
            $parentNode = $em->getRepository(ResourceNode::class)->find($parentResourceNodeId);
            if ($parentNode instanceof ResourceNode) {
                foreach ($parentNode->getResourceLinks() as $rl) {
                    if ($rl instanceof ResourceLink && null !== $rl->getCourse()) {
                        $course = $rl->getCourse();
                        $courses[(int) $course->getId()] = $course;
                    }
                }
            }
        }

        return array_values($courses);
    }
}
