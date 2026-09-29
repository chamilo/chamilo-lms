<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DocumentManager as ChamiloDocumentManager;
use Onlyoffice\DocsIntegrationSdk\Manager\Document\DocumentManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OnlyofficeDocumentManager extends DocumentManager
{
    private $docInfo;

    public function __construct($settingsManager, array $docInfo, $formats = null, $systemLangCode = 'en')
    {
        $formats = new OnlyofficeFormatsManager();
        parent::__construct($settingsManager, $formats, $systemLangCode);
        $this->docInfo = $docInfo;
    }

    public function getDocumentKey(string $fileId, $courseCode, bool $embedded = false)
    {
        $parts = [
            (string) $courseCode,
            (string) $fileId,
            (string) ($this->docInfo['title'] ?? ''),
            (string) ($this->docInfo['path'] ?? ''),
            (string) ($this->docInfo['iid'] ?? ''),
        ];

        if (!empty($this->docInfo['absolute_path']) && is_file($this->docInfo['absolute_path'])) {
            $mtime = @filemtime($this->docInfo['absolute_path']);
            if (false !== $mtime) {
                $parts[] = (string) $mtime;
            }
        }

        $rawKey = implode('|', $parts);

        return self::generateRevisionId($rawKey);
    }

    public function getDocumentName(string $fileId = '')
    {
        return $this->docInfo['title'];
    }

    public static function getLangMapping()
    {
    }

    public function getFileUrl(string $fileId)
    {
        $data = [
            'type' => 'download',
            'courseId' => api_get_course_int_id(),
            'userId' => api_get_user_id(),
            'docId' => $fileId,
            'sessionId' => api_get_session_id(),
        ];

        if (!empty($this->getGroupId())) {
            $data['groupId'] = $this->getGroupId();
        }

        if (isset($this->docInfo['path']) && str_contains($this->docInfo['path'], 'exercises/')) {
            $data['doctype'] = 'exercise';
            $data['docPath'] = urlencode($this->docInfo['path']);
        }

        $jwtManager = new OnlyofficeJwtManager($this->settingsManager);
        $hashUrl = $jwtManager->getHash($data);

        return api_get_path(WEB_PLUGIN_PATH).$this->settingsManager->plugin->get_name().'/callback.php?hash='.$hashUrl;
    }

    public function getGroupId()
    {
        foreach (['groupId', 'gid', 'gidReq'] as $parameter) {
            if (isset($_GET[$parameter]) && '' !== (string) $_GET[$parameter]) {
                return (int) $_GET[$parameter];
            }
        }

        return (int) api_get_group_id();
    }

    public function getCallbackUrl(string $fileId)
    {
        $data = [
            'type' => 'track',
            'courseId' => api_get_course_int_id(),
            'userId' => api_get_user_id(),
            'docId' => $fileId,
            'sessionId' => api_get_session_id(),
        ];

        if (!empty($this->getGroupId())) {
            $data['groupId'] = $this->getGroupId();
        }

        if (isset($this->docInfo['path']) && str_contains($this->docInfo['path'], 'exercises/')) {
            $data['doctype'] = 'exercise';
            $data['docPath'] = urlencode($this->docInfo['path']);
        }

        $jwtManager = new OnlyofficeJwtManager($this->settingsManager);
        $hashUrl = $jwtManager->getHash($data);

        return api_get_path(WEB_PLUGIN_PATH).'Onlyoffice/callback.php?hash='.$hashUrl;
    }

    public function getGobackUrl(string $fileId): string
    {
        if (empty($this->docInfo)) {
            return '';
        }

        $returnUrl = trim((string) ($this->docInfo['return_url'] ?? ''));
        if ('' !== $returnUrl) {
            return $returnUrl;
        }

        if (isset($this->docInfo['path']) && str_contains((string) $this->docInfo['path'], 'exercises/')) {
            $query = http_build_query(
                [
                    'cidReq' => (string) api_get_course_id(),
                    'id_session' => (int) api_get_session_id(),
                    'gidReq' => (int) $this->getGroupId(),
                    'exerciseId' => (int) ($this->docInfo['exercise_id'] ?? 0),
                ],
                '',
                '&',
                PHP_QUERY_RFC3986
            );

            return api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$query;
        }

        return self::getUrlToLocation(
            api_get_course_id(),
            api_get_session_id(),
            $this->getGroupId(),
            $this->docInfo['parent_id'] ?? 0,
            $this->docInfo['path'] ?? ''
        );
    }

    /**
     * Return location file in Chamilo documents or exercises.
     */
    public static function getUrlToLocation($courseCode, $sessionId, $groupId, $folderId, $filePath = ''): string
    {
        $isExercise = '' !== (string) $filePath && str_contains((string) $filePath, 'exercises/');
        $query = [
            'cidReq' => (string) $courseCode,
            'id_session' => (int) $sessionId,
            'gidReq' => (int) $groupId,
        ];

        if ($isExercise) {
            $query['exerciseId'] = (int) $folderId;

            return api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );
        }

        $query['id'] = (int) $folderId;

        return api_get_path(WEB_CODE_PATH).'document/document.php?'.http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    public function getCreateUrl(string $fileId)
    {
    }

    /**
     * Get the value of docInfo.
     */
    public function getDocInfo($elem = null)
    {
        if (empty($elem)) {
            return $this->docInfo;
        } else {
            if (isset($this->docInfo[$elem])) {
                return $this->docInfo[$elem];
            }

            return [];
        }
    }

    /**
     * Set the value of docInfo.
     */
    public function setDocInfo($docInfo)
    {
        $this->docInfo = $docInfo;
    }

    /**
     * Return file extension by file type.
     */
    public static function getDocExtByType(string $type): string
    {
        if ('text' === $type) {
            return 'docx';
        }
        if ('spreadsheet' === $type) {
            return 'xlsx';
        }
        if ('presentation' === $type) {
            return 'pptx';
        }
        if ('formTemplate' === $type) {
            return 'pdf';
        }

        return '';
    }

    /**
     * Create a new document using the current CDocument/ResourceNode storage model.
     */
    public static function createFile(
        string $basename,
        string $fileExt,
        int $folderId,
        int $userId,
        int $sessionId,
        int $courseId,
        int $groupId,
        string $templatePath = '',
        int $parentResourceNodeId = 0,
    ): array {
        $course = api_get_course_entity($courseId);
        if (!$course) {
            return ['error' => 'impossibleCreateFile'];
        }

        $session = $sessionId > 0 ? api_get_session_entity($sessionId) : null;
        if ($sessionId > 0 && null === $session) {
            return ['error' => 'impossibleCreateFile'];
        }

        $group = $groupId > 0 ? api_get_group_entity($groupId) : null;
        if ($groupId > 0 && null === $group) {
            return ['error' => 'impossibleCreateFile'];
        }

        $documentRepository = Container::getDocumentRepository();
        if (!$documentRepository instanceof CDocumentRepository) {
            return ['error' => 'impossibleCreateFile'];
        }

        $parentNode = self::resolveCreateParentNode(
            $documentRepository,
            $courseId,
            $folderId,
            $parentResourceNodeId
        );
        if (!$parentNode instanceof ResourceNode) {
            return ['error' => 'impossibleCreateFile'];
        }

        $fileExt = strtolower(trim($fileExt));
        if (!in_array($fileExt, ['docx', 'xlsx', 'pptx', 'pdf'], true)) {
            return ['error' => 'impossibleCreateFile'];
        }

        $safeBasename = trim(Security::remove_XSS($basename));
        $safeBasename = str_replace(['/', '\\', '..'], '', $safeBasename);
        if ('' === $safeBasename) {
            return ['error' => 'impossibleCreateFile'];
        }

        $fileTitle = $safeBasename.'.'.$fileExt;
        if (self::documentExistsInContext(
            $documentRepository,
            $parentNode,
            $fileTitle,
            $courseId,
            $sessionId,
            $groupId
        )) {
            return ['error' => 'fileIsExist'];
        }

        if ('' === $templatePath) {
            $templatePath = TemplateManager::getEmptyTemplate($fileExt);
        }

        $content = @file_get_contents($templatePath);
        if (false === $content) {
            return ['error' => 'impossibleCreateFile'];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'onlyoffice_');
        if (false === $tempPath) {
            return ['error' => 'impossibleCreateFile'];
        }

        try {
            if (false === file_put_contents($tempPath, $content)) {
                return ['error' => 'impossibleCreateFile'];
            }

            $mimeType = ChamiloDocumentManager::file_get_mime_type($fileTitle);
            $uploadedFile = new UploadedFile($tempPath, $fileTitle, $mimeType, null, true);
            $visibility = 'visible' === ChamiloDocumentManager::getDocumentDefaultVisibility($course->getCode())
                ? ResourceLink::VISIBILITY_PUBLISHED
                : ResourceLink::VISIBILITY_DRAFT;

            $resourceNode = $documentRepository->createFileInFolder(
                $course,
                $parentNode,
                $uploadedFile,
                '',
                $visibility,
                $session,
                $group
            );

            $document = $documentRepository->findOneBy(['resourceNode' => $resourceNode]);
            if (!$document instanceof CDocument || empty($document->getIid())) {
                return ['error' => 'impossibleCreateFile'];
            }

            return ['documentId' => (int) $document->getIid()];
        } catch (Throwable $exception) {
            error_log('[Onlyoffice] Failed to create CDocument: '.$exception->getMessage());

            return ['error' => 'impossibleCreateFile'];
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private static function resolveCreateParentNode(
        CDocumentRepository $documentRepository,
        int $courseId,
        int $folderId,
        int $parentResourceNodeId,
    ): ?ResourceNode {
        $course = api_get_course_entity($courseId);
        if (!$course) {
            return null;
        }

        $courseRoot = $course->getResourceNode();

        if ($parentResourceNodeId > 0) {
            $entityManager = Database::getManager();
            $parentNode = $entityManager->getRepository(ResourceNode::class)->find($parentResourceNodeId);
            if (!$parentNode instanceof ResourceNode) {
                return null;
            }

            // The modern Documents Vue root is the course ResourceNode itself.
            // Course root nodes do not need their own ResourceLink back to the course.
            if ($courseRoot instanceof ResourceNode && $courseRoot->getId() === $parentNode->getId()) {
                return $parentNode;
            }

            if (!self::resourceNodeBelongsToCourse($parentNode, $courseId)) {
                return null;
            }

            $documentsRoot = $documentRepository->getCourseDocumentsRootNode($course);
            if ($documentsRoot instanceof ResourceNode && $documentsRoot->getId() === $parentNode->getId()) {
                return $parentNode;
            }

            $parentDocument = $documentRepository->findOneBy(['resourceNode' => $parentNode]);
            if ($parentDocument instanceof CDocument && 'folder' === $parentDocument->getFiletype()) {
                return $parentNode;
            }

            return null;
        }

        if ($folderId > 0) {
            $parentDocument = $documentRepository->find($folderId);
            if (!$parentDocument instanceof CDocument || 'folder' !== $parentDocument->getFiletype()) {
                return null;
            }

            $parentNode = $parentDocument->getResourceNode();
            if (!$parentNode instanceof ResourceNode || !self::resourceNodeBelongsToCourse($parentNode, $courseId)) {
                return null;
            }

            return $parentNode;
        }

        if ($courseRoot instanceof ResourceNode) {
            return $courseRoot;
        }

        return $documentRepository->getCourseDocumentsRootNode($course)
            ?? $documentRepository->ensureCourseDocumentsRootNode($course);
    }

    private static function documentExistsInContext(
        CDocumentRepository $documentRepository,
        ResourceNode $parentNode,
        string $title,
        int $courseId,
        int $sessionId,
        int $groupId,
    ): bool {
        $entityManager = Database::getManager();
        $nodes = $entityManager->getRepository(ResourceNode::class)->findBy([
            'parent' => $parentNode->getId(),
            'title' => $title,
        ]);

        foreach ($nodes as $node) {
            if (!$node instanceof ResourceNode) {
                continue;
            }

            $document = $documentRepository->findOneBy(['resourceNode' => $node]);
            if (!$document instanceof CDocument || 'file' !== $document->getFiletype()) {
                continue;
            }

            foreach ($node->getResourceLinks() as $resourceLink) {
                $course = $resourceLink->getCourse();
                $session = $resourceLink->getSession();
                $group = $resourceLink->getGroup();

                if (!$course || $courseId !== (int) $course->getId()) {
                    continue;
                }

                $linkedSessionId = $session ? (int) $session->getId() : 0;
                $linkedGroupId = $group ? (int) $group->getIid() : 0;

                if ($sessionId === $linkedSessionId && $groupId === $linkedGroupId) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function resourceNodeBelongsToCourse(ResourceNode $resourceNode, int $courseId): bool
    {
        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            $course = $resourceLink->getCourse();
            if ($course && $courseId === (int) $course->getId()) {
                return true;
            }
        }

        return false;
    }
}

