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

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;

const ONLYOFFICE_CALLBACK_LOG_ENABLED = true;

$plugin = OnlyofficePlugin::create();

if (empty($_GET['hash'])) {
    sendNoCacheHeaders();
    @header('Content-Type: application/json');

    exit(json_encode([
        'status' => 'error',
        'error' => 'Missing hash',
    ]));
}

@header('X-Robots-Tag: noindex');
@header('X-Content-Type-Options: nosniff');

$appSettings = new OnlyofficeAppsettings($plugin);
$jwtManager = new OnlyofficeJwtManager($appSettings);

$hash = (string) $_GET['hash'];
$signingKey = $jwtManager->getSigningKey();

[$hashData, $error] = $jwtManager->readHash($hash, $signingKey);

if (null === $hashData) {
    sendNoCacheHeaders();
    @header('Content-Type: application/json');
    onlyofficeLog('ERROR', 'Invalid hash', ['error' => $error]);

    exit(json_encode([
        'status' => 'error',
        'error' => $error,
    ]));
}

$type = (string) getHashValue($hashData, 'type', '');
$courseId = (int) getHashValue($hashData, 'courseId', 0);
$userId = (int) getHashValue($hashData, 'userId', 0);
$docId = (int) getHashValue($hashData, 'docId', 0);
$groupId = (int) getHashValue($hashData, 'groupId', 0);
$sessionId = (int) getHashValue($hashData, 'sessionId', 0);

$docPathFromQuery = isset($_GET['docPath']) ? urldecode((string) $_GET['docPath']) : '';
$docPath = '' !== $docPathFromQuery
    ? $docPathFromQuery
    : (string) getHashValue($hashData, 'docPath', '');

$courseInfo = [];
$courseCode = '';

if ($courseId > 0) {
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = is_array($courseInfo) ? ($courseInfo['code'] ?? '') : '';
}

onlyofficeLog('DEBUG', 'Callback entry', [
    'type' => $type,
    'courseId' => $courseId,
    'courseCode' => $courseCode,
    'userId' => $userId,
    'docId' => $docId,
    'groupId' => $groupId,
    'sessionId' => $sessionId,
    'docPath' => $docPath,
]);

if ($userId <= 0) {
    sendNoCacheHeaders();
    @header('Content-Type: application/json');

    exit(json_encode([
        'status' => 'error',
        'error' => 'User not found',
    ]));
}

$userInfo = api_get_user_info($userId);

if (empty($userInfo)) {
    sendNoCacheHeaders();
    @header('Content-Type: application/json');

    exit(json_encode([
        'status' => 'error',
        'error' => 'User not found',
    ]));
}

if (api_is_anonymous()) {
    $loggedUser = [
        'user_id' => $userInfo['id'],
        'status' => $userInfo['status'],
        'uidReset' => true,
    ];

    Session::write('_user', $loggedUser);
    Login::init_user($loggedUser['user_id'], true);
}

if (PHP_SESSION_ACTIVE === session_status()) {
    session_write_close();
}

switch ($type) {
    case 'track':
        sendNoCacheHeaders();
        @header('Content-Type: application/json');
        exit(json_encode(track()));

    case 'download':
        download();
        exit;

    case 'empty':
        emptyFile();
        exit;

    default:
        sendNoCacheHeaders();
        @header('Content-Type: application/json');

        exit(json_encode([
            'status' => 'error',
            'error' => '404 Method not found',
        ]));
}

/**
 * Handle request from the document server with the document status information.
 */
function track(): array
{
    global $appSettings;
    global $jwtManager;
    global $courseCode;
    global $docId;
    global $docPath;
    global $sessionId;

    $bodyStream = file_get_contents('php://input');
    if (false === $bodyStream || '' === $bodyStream) {
        onlyofficeLog('ERROR', 'Empty callback body');

        return ['error' => 1];
    }

    $data = json_decode($bodyStream, true);
    if (!is_array($data)) {
        onlyofficeLog('ERROR', 'Invalid callback JSON');

        return ['error' => 1];
    }

    $status = (int) ($data['status'] ?? 0);

    if (\in_array($status, [1, 4], true)) {
        onlyofficeLog('INFO', 'Ignored callback status', ['status' => $status]);

        return ['error' => 0];
    }

    $payload = validateDocumentServerToken($data, $jwtManager, $appSettings);
    if (is_array($payload) && isset($payload['__error'])) {
        onlyofficeLog('ERROR', 'Token validation failed', ['error' => $payload['__error']]);

        return ['error' => 1];
    }

    if (\in_array($status, [3, 7], true)) {
        onlyofficeLog('WARNING', 'Document server reported save error', ['status' => $status]);

        return ['error' => 0];
    }

    if (!\in_array($status, [2, 6], true)) {
        onlyofficeLog('INFO', 'Ignored callback status', ['status' => $status]);

        return ['error' => 0];
    }

    $resolved = resolveDocumentSource($docId, $courseCode, $sessionId, $docPath);
    if (null === $resolved) {
        onlyofficeLog('ERROR', 'File not found for save', [
            'docId' => $docId,
            'docPath' => $docPath,
            'courseCode' => $courseCode,
            'sessionId' => $sessionId,
        ]);

        return ['error' => 1];
    }

    $downloadUrl = extractCallbackDownloadUrl($data, $payload);
    if ('' === $downloadUrl) {
        onlyofficeLog('ERROR', 'Missing callback download URL');

        return ['error' => 1];
    }

    $newContent = fetchRemoteBinary($downloadUrl);
    if (null === $newContent) {
        onlyofficeLog('ERROR', 'Failed to fetch updated document', [
            'downloadUrl' => $downloadUrl,
        ]);

        return ['error' => 1];
    }

    $writeResult = writeResolvedDocumentContent($resolved, $newContent);
    if (false === $writeResult) {
        onlyofficeLog('ERROR', 'Failed to write updated document', [
            'docId' => $docId,
            'documentKey' => $resolved['documentKey'] ?? '',
            'filePath' => $resolved['filePath'] ?? '',
            'storagePath' => $resolved['storagePath'] ?? '',
        ]);

        return ['error' => 1];
    }

    syncResolvedDocumentMetadata($resolved, strlen($newContent));

    onlyofficeLog('SUCCESS', 'File saved', [
        'docId' => $docId,
        'documentKey' => $resolved['documentKey'] ?? '',
        'filePath' => $resolved['filePath'] ?? '',
        'storagePath' => $resolved['storagePath'] ?? '',
        'size' => strlen($newContent),
        'sha1' => sha1($newContent),
    ]);

    return ['error' => 0];
}

/**
 * Download the file for ONLYOFFICE.
 */
function download(): void
{
    global $courseCode;
    global $docId;
    global $docPath;
    global $sessionId;

    $resolved = resolveDocumentSource($docId, $courseCode, $sessionId, $docPath);

    if (null === $resolved) {
        sendNoCacheHeaders();
        @header('Content-Type: application/json');
        onlyofficeLog('ERROR', 'Download file not found', [
            'docId' => $docId,
            'docPath' => $docPath,
            'courseCode' => $courseCode,
            'sessionId' => $sessionId,
        ]);

        echo json_encode([
            'status' => 'error',
            'error' => 'File not found',
        ]);

        return;
    }

    $title = (string) ($resolved['title'] ?? 'document');
    $safeFilename = buildSafeDownloadFilename($title);
    $mimeType = (string) ($resolved['mimeType'] ?? getMimeTypeFromFilename($title));
    $size = (int) ($resolved['size'] ?? 0);

    sendNoCacheHeaders();
    @header('Content-Type: '.$mimeType);
    @header('Content-Disposition: inline; filename="'.$safeFilename.'"');
    @header('Content-Transfer-Encoding: binary');

    if ($size > 0) {
        @header('Content-Length: '.(string) $size);
    }

    onlyofficeLog('DEBUG', 'Download source resolved', [
        'docId' => $docId,
        'title' => $title,
        'mimeType' => $mimeType,
        'size' => $size,
        'storagePath' => $resolved['storagePath'] ?? '',
        'filePath' => $resolved['filePath'] ?? '',
    ]);

    if (!empty($resolved['stream']) && \is_resource($resolved['stream'])) {
        fpassthru($resolved['stream']);
        fclose($resolved['stream']);

        return;
    }

    if (!empty($resolved['filePath']) && file_exists($resolved['filePath'])) {
        readfile($resolved['filePath']);

        return;
    }

    sendNoCacheHeaders();
    @header('Content-Type: application/json');
    onlyofficeLog('ERROR', 'Download source resolved but unreadable', [
        'docId' => $docId,
    ]);

    echo json_encode([
        'status' => 'error',
        'error' => 'File not found',
    ]);
}

/**
 * Download an empty file template.
 */
function emptyFile(): void
{
    $template = TemplateManager::getEmptyTemplate('docx');

    if (!$template) {
        sendNoCacheHeaders();
        @header('Content-Type: application/json');

        echo json_encode([
            'status' => 'error',
            'error' => 'File not found',
        ]);

        return;
    }

    sendNoCacheHeaders();
    @header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    @header('Content-Disposition: inline; filename="docx.docx"');

    readfile($template);
}

/**
 * Resolve a document source in C2 first, then legacy.
 */
function resolveDocumentSource(int $docId, string $courseCode, int $sessionId, string $docPath): ?array
{
    if ('' !== $docPath) {
        $filePath = api_get_path(SYS_COURSE_PATH).$docPath;

        if (file_exists($filePath)) {
            onlyofficeLog('DEBUG', 'Resolved direct docPath', [
                'docPath' => $docPath,
                'filePath' => $filePath,
            ]);

            return [
                'filePath' => $filePath,
                'stream' => null,
                'storagePath' => '',
                'title' => basename($filePath),
                'documentKey' => basename($docPath),
                'resourceFileId' => 0,
                'resourceNodeId' => 0,
                'entityManager' => null,
                'resourceNodeRepository' => null,
                'size' => (int) filesize($filePath),
                'mimeType' => @mime_content_type($filePath) ?: getMimeTypeFromFilename($filePath),
            ];
        }

        onlyofficeLog('WARNING', 'Direct docPath not found', [
            'docPath' => $docPath,
            'filePath' => $filePath,
        ]);
    }

    if ($docId > 0) {
        $resolved = resolveDocumentSourceFromC2($docId);
        if (null !== $resolved) {
            return $resolved;
        }

        if ('' !== $courseCode) {
            $legacyDocInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

            if ($legacyDocInfo && !empty($legacyDocInfo['absolute_path']) && file_exists($legacyDocInfo['absolute_path'])) {
                $filePath = (string) $legacyDocInfo['absolute_path'];
                $title = (string) ($legacyDocInfo['title'] ?? basename($filePath));

                onlyofficeLog('DEBUG', 'Resolved legacy document', [
                    'docId' => $docId,
                    'filePath' => $filePath,
                    'title' => $title,
                ]);

                return [
                    'filePath' => $filePath,
                    'stream' => null,
                    'storagePath' => '',
                    'title' => $title,
                    'documentKey' => (string) $docId,
                    'resourceFileId' => 0,
                    'resourceNodeId' => 0,
                    'entityManager' => null,
                    'resourceNodeRepository' => null,
                    'size' => (int) filesize($filePath),
                    'mimeType' => @mime_content_type($filePath) ?: getMimeTypeFromFilename($title),
                ];
            }
        }
    }

    onlyofficeLog('ERROR', 'resolveDocumentSource failed', [
        'docId' => $docId,
        'docPath' => $docPath,
        'courseCode' => $courseCode,
        'sessionId' => $sessionId,
    ]);

    return null;
}

/**
 * Resolve C2 document using CDocument -> ResourceNode -> ResourceFile.
 */
function resolveDocumentSourceFromC2(int $docId): ?array
{
    $entityManager = getEntityManager();
    if (null === $entityManager) {
        onlyofficeLog('ERROR', 'Entity manager could not be resolved');

        return null;
    }

    /** @var CDocument|null $document */
    $document = $entityManager->getRepository(CDocument::class)->find($docId);
    if (!$document instanceof CDocument) {
        onlyofficeLog('ERROR', 'CDocument not found', [
            'docId' => $docId,
        ]);

        return null;
    }

    $resourceNode = $document->getResourceNode();
    if (null === $resourceNode) {
        onlyofficeLog('ERROR', 'ResourceNode missing for document', [
            'docId' => $docId,
        ]);

        return null;
    }

    $resourceFile = $resourceNode->getFirstResourceFile();
    if (!$resourceFile instanceof ResourceFile) {
        onlyofficeLog('ERROR', 'ResourceFile missing for document', [
            'docId' => $docId,
            'resourceNodeId' => (int) $resourceNode->getId(),
        ]);

        return null;
    }

    $resourceNodeRepository = getResourceNodeRepository();
    if (null === $resourceNodeRepository) {
        onlyofficeLog('ERROR', 'ResourceNodeRepository could not be resolved');

        return null;
    }

    $storagePath = '';
    try {
        $storagePath = (string) $resourceNodeRepository->getFilename($resourceFile);
    } catch (\Throwable $e) {
        onlyofficeLog('WARNING', 'Failed to resolve storage filename', [
            'message' => $e->getMessage(),
        ]);
    }

    try {
        $stream = $resourceNodeRepository->getResourceNodeFileStream($resourceNode, $resourceFile);
    } catch (\Throwable $e) {
        onlyofficeLog('ERROR', 'Failed to open resource stream', [
            'message' => $e->getMessage(),
        ]);

        return null;
    }

    if (!\is_resource($stream)) {
        onlyofficeLog('ERROR', 'Resource stream is not available', [
            'docId' => $docId,
            'resourceNodeId' => (int) $resourceNode->getId(),
            'resourceFileId' => (int) $resourceFile->getId(),
            'storagePath' => $storagePath,
            'originalName' => (string) $resourceFile->getOriginalName(),
            'title' => (string) $resourceFile->getTitle(),
        ]);

        return null;
    }

    $title = (string) ($resourceFile->getOriginalName() ?: $document->getTitle() ?: $resourceNode->getTitle());
    $size = (int) ($resourceFile->getSize() ?? 0);
    $mimeType = (string) ($resourceFile->getMimeType() ?: getMimeTypeFromFilename($title));

    onlyofficeLog('DEBUG', 'Resolved C2 resource file', [
        'docId' => $docId,
        'resourceNodeId' => (int) $resourceNode->getId(),
        'resourceFileId' => (int) $resourceFile->getId(),
        'storagePath' => $storagePath,
        'title' => $title,
        'size' => $size,
        'mimeType' => $mimeType,
    ]);

    return [
        'filePath' => null,
        'stream' => $stream,
        'storagePath' => $storagePath,
        'title' => $title,
        'documentKey' => (string) $docId,
        'resourceFileId' => (int) $resourceFile->getId(),
        'resourceNodeId' => (int) $resourceNode->getId(),
        'entityManager' => $entityManager,
        'resourceNodeRepository' => $resourceNodeRepository,
        'size' => $size,
        'mimeType' => $mimeType,
    ];
}

/**
 * Write content back to the resolved storage.
 */
function writeResolvedDocumentContent(array $resolved, string $content): bool
{
    if (!empty($resolved['filePath'])) {
        $filePath = (string) $resolved['filePath'];

        if (false === @file_put_contents($filePath, $content, LOCK_EX)) {
            return false;
        }

        clearstatcache(true, $filePath);

        return true;
    }

    $resourceNodeRepository = $resolved['resourceNodeRepository'] ?? null;
    $storagePath = (string) ($resolved['storagePath'] ?? '');

    if ($resourceNodeRepository instanceof ResourceNodeRepository && '' !== $storagePath) {
        try {
            $filesystem = $resourceNodeRepository->getFileSystem();

            try {
                if ($filesystem->fileExists($storagePath)) {
                    $filesystem->delete($storagePath);
                }
            } catch (\Throwable $e) {
                onlyofficeLog('WARNING', 'Flysystem delete before write failed', [
                    'storagePath' => $storagePath,
                    'message' => $e->getMessage(),
                ]);
            }

            $filesystem->write($storagePath, $content);

            onlyofficeLog('DEBUG', 'Content written through Flysystem', [
                'storagePath' => $storagePath,
                'size' => strlen($content),
                'sha1' => sha1($content),
            ]);

            return true;
        } catch (\Throwable $e) {
            onlyofficeLog('ERROR', 'Flysystem write failed', [
                'storagePath' => $storagePath,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    return false;
}

/**
 * Update DB metadata after save.
 */
function syncResolvedDocumentMetadata(array $resolved, ?int $contentSize = null): void
{
    $entityManager = $resolved['entityManager'] ?? null;
    $resourceFileId = (int) ($resolved['resourceFileId'] ?? 0);
    $resourceNodeId = (int) ($resolved['resourceNodeId'] ?? 0);
    $title = (string) ($resolved['title'] ?? '');

    if (null === $entityManager || $resourceFileId <= 0) {
        return;
    }

    $size = max(0, (int) $contentSize);
    $mimeType = getMimeTypeFromFilename($title);

    if (!empty($resolved['filePath']) && file_exists((string) $resolved['filePath'])) {
        $filePath = (string) $resolved['filePath'];
        $size = (int) filesize($filePath);
        $mimeType = @mime_content_type($filePath) ?: $mimeType;
    }

    try {
        $connection = $entityManager->getConnection();

        $connection->executeStatement(
            'UPDATE resource_file
             SET size = :size,
                 mime_type = :mime_type,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'size' => $size,
                'mime_type' => $mimeType,
                'id' => $resourceFileId,
            ]
        );

        if ($resourceNodeId > 0) {
            $connection->executeStatement(
                'UPDATE resource_node
                 SET updated_at = NOW()
                 WHERE id = :id',
                [
                    'id' => $resourceNodeId,
                ]
            );
        }

        onlyofficeLog('DEBUG', 'Metadata synced', [
            'resourceFileId' => $resourceFileId,
            'resourceNodeId' => $resourceNodeId,
            'size' => $size,
            'mimeType' => $mimeType,
        ]);
    } catch (\Throwable $e) {
        onlyofficeLog('WARNING', 'Metadata sync failed', [
            'message' => $e->getMessage(),
        ]);
    }
}

/**
 * Validate JWT from ONLYOFFICE document server.
 */
function validateDocumentServerToken(array $data, $jwtManager, $appSettings): object|array|null
{
    if (!$jwtManager->isJwtEnabled()) {
        return null;
    }

    $token = '';

    if (!empty($data['token']) && is_string($data['token'])) {
        $token = $data['token'];
    } else {
        $token = readBearerToken((string) $appSettings->getJwtHeader());
    }

    if ('' === $token) {
        return null;
    }

    try {
        $decoded = $jwtManager->decode($token, $appSettings->getJwtKey());

        if (is_object($decoded) && isset($decoded->payload) && is_object($decoded->payload)) {
            return $decoded->payload;
        }

        if (is_object($decoded)) {
            return $decoded;
        }

        return null;
    } catch (\Throwable $e) {
        return ['__error' => '403 Access denied'];
    }
}

/**
 * Extract the updated file URL from callback body or payload.
 */
function extractCallbackDownloadUrl(array $data, mixed $payload): string
{
    if (!empty($data['url']) && is_string($data['url'])) {
        return $data['url'];
    }

    if (is_object($payload) && property_exists($payload, 'url') && is_string($payload->url)) {
        return $payload->url;
    }

    return '';
}

/**
 * Fetch remote binary data.
 */
function fetchRemoteBinary(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        if (false !== $ch) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $content = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if (false !== $content && $httpCode >= 200 && $httpCode < 300) {
                return (string) $content;
            }

            onlyofficeLog('WARNING', 'cURL download failed', [
                'url' => $url,
                'httpCode' => $httpCode,
                'error' => $error,
            ]);
        }
    }

    $content = @file_get_contents($url);

    if (false === $content) {
        return null;
    }

    return $content;
}

/**
 * Read a property from decoded hash payload safely.
 */
function getHashValue(mixed $data, string $key, mixed $default = null): mixed
{
    if (is_array($data) && array_key_exists($key, $data)) {
        return $data[$key];
    }

    if (is_object($data) && property_exists($data, $key)) {
        return $data->{$key};
    }

    return $default;
}

/**
 * Read Bearer token from request headers.
 */
function readBearerToken(string $headerName): string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    foreach ($headers as $name => $value) {
        if (0 === strcasecmp((string) $name, $headerName)) {
            $value = (string) $value;

            if (str_starts_with($value, 'Bearer ')) {
                return trim(substr($value, 7));
            }

            return trim($value);
        }
    }

    return '';
}

/**
 * Resolve Doctrine entity manager.
 */
function getEntityManager()
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            if (Container::$container->has('doctrine.orm.entity_manager')) {
                $entityManager = Container::$container->get('doctrine.orm.entity_manager');

                onlyofficeLog('DEBUG', 'Entity manager resolved from container', [
                    'serviceId' => 'doctrine.orm.entity_manager',
                    'class' => get_class($entityManager),
                ]);

                return $entityManager;
            }

            if (Container::$container->has('doctrine')) {
                $doctrine = Container::$container->get('doctrine');
                $entityManager = $doctrine->getManager();

                onlyofficeLog('DEBUG', 'Entity manager resolved from doctrine service', [
                    'serviceId' => 'doctrine',
                    'class' => get_class($entityManager),
                ]);

                return $entityManager;
            }
        }
    } catch (\Throwable $e) {
        onlyofficeLog('WARNING', 'Doctrine container access failed', [
            'message' => $e->getMessage(),
        ]);
    }

    if (class_exists('Database') && method_exists('Database', 'getManager')) {
        try {
            $entityManager = Database::getManager();

            onlyofficeLog('DEBUG', 'Entity manager resolved from Database::getManager', [
                'class' => get_class($entityManager),
            ]);

            return $entityManager;
        } catch (\Throwable $e) {
            onlyofficeLog('WARNING', 'Database::getManager failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    return null;
}

/**
 * Resolve ResourceNodeRepository.
 */
function getResourceNodeRepository(): ?ResourceNodeRepository
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            if (Container::$container->has(ResourceNodeRepository::class)) {
                $repo = Container::$container->get(ResourceNodeRepository::class);

                if ($repo instanceof ResourceNodeRepository) {
                    onlyofficeLog('DEBUG', 'ResourceNodeRepository resolved by class name');

                    return $repo;
                }
            }

            $serviceIds = [
                'Chamilo\\CoreBundle\\Repository\\ResourceNodeRepository',
                'chamilo.repository.resource_node',
            ];

            foreach ($serviceIds as $serviceId) {
                if (!Container::$container->has($serviceId)) {
                    continue;
                }

                $repo = Container::$container->get($serviceId);

                if ($repo instanceof ResourceNodeRepository) {
                    onlyofficeLog('DEBUG', 'ResourceNodeRepository resolved by service id', [
                        'serviceId' => $serviceId,
                    ]);

                    return $repo;
                }
            }
        }
    } catch (\Throwable $e) {
        onlyofficeLog('WARNING', 'ResourceNodeRepository access failed', [
            'message' => $e->getMessage(),
        ]);
    }

    return null;
}

/**
 * Build a safe download filename.
 */
function buildSafeDownloadFilename(string $filename): string
{
    $filename = trim($filename);
    if ('' === $filename) {
        return 'document';
    }

    return preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'document';
}

/**
 * Resolve mime type from filename.
 */
function getMimeTypeFromFilename(string $filename): string
{
    $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

    return match ($extension) {
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppt' => 'application/vnd.ms-powerpoint',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        default => 'application/octet-stream',
    };
}

/**
 * Send anti-cache headers.
 */
function sendNoCacheHeaders(): void
{
    @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    @header('Expires: 0');
}

/**
 * Structured logger helper.
 */
function onlyofficeLog(string $level, string $message, array $context = []): void
{
    if (!ONLYOFFICE_CALLBACK_LOG_ENABLED) {
        return;
    }

    $line = 'ONLYOFFICE CALLBACK: '.$level.' - '.$message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false !== $json) {
            $line .= ' | '.$json;
        }
    }

    error_log($line);
}