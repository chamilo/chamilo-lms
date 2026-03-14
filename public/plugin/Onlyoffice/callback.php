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

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;
use Throwable;

$plugin = OnlyofficePlugin::create();

if (empty($_GET['hash'])) {
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
    @header('Content-Type: application/json');
    error_log('ONLYOFFICE CALLBACK: ERROR - Invalid hash: '.$error);

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

if ($userId <= 0) {
    @header('Content-Type: application/json');

    exit(json_encode([
        'status' => 'error',
        'error' => 'User not found',
    ]));
}

$userInfo = api_get_user_info($userId);

if (empty($userInfo)) {
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
} else {
    $userId = api_get_user_id();
}

switch ($type) {
    case 'track':
        @header('Content-Type: application/json');

        exit(json_encode(track()));

    case 'download':
        download();
        exit;

    case 'empty':
        emptyFile();
        exit;

    default:
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
        error_log('ONLYOFFICE CALLBACK: ERROR - Empty callback body.');

        return ['error' => 1];
    }

    $data = json_decode($bodyStream, true);
    if (!is_array($data)) {
        error_log('ONLYOFFICE CALLBACK: ERROR - Invalid callback JSON.');

        return ['error' => 1];
    }

    $status = (int) ($data['status'] ?? 0);

    // 4 = closed with no changes
    if (4 === $status) {
        return ['error' => 0];
    }

    $payload = validateDocumentServerToken($data, $jwtManager, $appSettings);
    if (is_array($payload) && isset($payload['__error'])) {
        error_log('ONLYOFFICE CALLBACK: ERROR - '.$payload['__error']);

        return ['error' => 1];
    }

    // 3 / 7 = save error on document server side, nothing to write locally
    if (\in_array($status, [3, 7], true)) {
        error_log('ONLYOFFICE CALLBACK: WARNING - Document server reported save error. Status: '.$status);

        return ['error' => 0];
    }

    // 2 = must save, 6 = force save
    if (!\in_array($status, [2, 6], true)) {
        error_log('ONLYOFFICE CALLBACK: INFO - Ignored callback status: '.$status);

        return ['error' => 0];
    }

    $resolved = resolveDocumentSource($docId, $courseCode, $sessionId, $docPath);
    if (null === $resolved) {
        error_log(
            'ONLYOFFICE CALLBACK: ERROR - File not found for save. '
            .'docId='.$docId.' docPath='.$docPath
        );

        return ['error' => 1];
    }

    $downloadUrl = extractCallbackDownloadUrl($data, $payload);
    if ('' === $downloadUrl) {
        error_log('ONLYOFFICE CALLBACK: ERROR - Missing callback download URL.');

        return ['error' => 1];
    }

    $newContent = @file_get_contents($downloadUrl);
    if (false === $newContent) {
        error_log('ONLYOFFICE CALLBACK: ERROR - Failed to fetch updated document from '.$downloadUrl);

        return ['error' => 1];
    }

    if (false === @file_put_contents($resolved['filePath'], $newContent)) {
        error_log('ONLYOFFICE CALLBACK: ERROR - Failed to save updated file into '.$resolved['filePath']);

        return ['error' => 1];
    }

    clearstatcache(true, $resolved['filePath']);
    syncResolvedDocumentMetadata($resolved);

    error_log(
        'ONLYOFFICE CALLBACK: SUCCESS - File saved. '
        .'docId='.$docId
        .' key='.$resolved['documentKey']
        .' path='.$resolved['filePath']
        .' size='.(string) @filesize($resolved['filePath'])
    );

    return ['error' => 0];
}

/**
 * Downloading file by the document service.
 */
function download(): void
{
    global $courseCode;
    global $docId;
    global $docPath;
    global $sessionId;

    $resolved = resolveDocumentSource($docId, $courseCode, $sessionId, $docPath);

    if (null === $resolved) {
        @header('Content-Type: application/json');
        error_log(
            'ONLYOFFICE CALLBACK: ERROR - Download file not found. '
            .'docId='.$docId.' docPath='.$docPath
        );

        echo json_encode([
            'status' => 'error',
            'error' => 'File not found',
        ]);

        return;
    }

    @header('Content-Type: application/octet-stream');
    @header('Content-Disposition: attachment; filename="'.basename((string) $resolved['title']).'"');
    @header('Content-Length: '.(string) filesize($resolved['filePath']));

    readfile($resolved['filePath']);
}

/**
 * Downloading empty file by the document service.
 */
function emptyFile(): void
{
    $template = TemplateManager::getEmptyTemplate('docx');

    if (!$template) {
        @header('Content-Type: application/json');

        echo json_encode([
            'status' => 'error',
            'error' => 'File not found',
        ]);

        return;
    }

    @header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    @header('Content-Disposition: attachment; filename="docx.docx"');

    readfile($template);
}

/**
 * Resolve a document source for callback download/save in C2 first, then fallback to legacy logic.
 */
function resolveDocumentSource(int $docId, string $courseCode, int $sessionId, string $docPath): ?array
{
    if ('' !== $docPath) {
        $filePath = api_get_path(SYS_COURSE_PATH).$docPath;

        if (file_exists($filePath)) {
            return [
                'filePath' => $filePath,
                'title' => basename($filePath),
                'documentKey' => basename($docPath),
                'resourceFileId' => 0,
                'resourceNodeId' => 0,
                'entityManager' => null,
            ];
        }
    }

    if ($docId > 0) {
        $resolved = resolveDocumentSourceFromC2($docId);
        if (null !== $resolved) {
            return $resolved;
        }

        if ('' !== $courseCode) {
            $legacyDocInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

            if ($legacyDocInfo && !empty($legacyDocInfo['absolute_path']) && file_exists($legacyDocInfo['absolute_path'])) {
                return [
                    'filePath' => $legacyDocInfo['absolute_path'],
                    'title' => $legacyDocInfo['title'] ?? basename($legacyDocInfo['absolute_path']),
                    'documentKey' => (string) $docId,
                    'resourceFileId' => 0,
                    'resourceNodeId' => 0,
                    'entityManager' => null,
                ];
            }
        }
    }

    return null;
}

/**
 * Resolve C2 document physical file using CDocument -> ResourceNode -> ResourceFile.
 */
function resolveDocumentSourceFromC2(int $docId): ?array
{
    $entityManager = getEntityManager();
    $storage = getVichStorage();

    if (null === $entityManager || null === $storage) {
        return null;
    }

    /** @var CDocument|null $document */
    $document = $entityManager->getRepository(CDocument::class)->find($docId);
    if (!$document instanceof CDocument) {
        return null;
    }

    $resourceNode = $document->getResourceNode();
    if (null === $resourceNode) {
        return null;
    }

    $resourceFile = $resourceNode->getFirstResourceFile();
    if (!$resourceFile instanceof ResourceFile) {
        return null;
    }

    $filePath = $storage->resolvePath($resourceFile, 'file');
    if (!is_string($filePath) || '' === $filePath || !file_exists($filePath)) {
        return null;
    }

    $title = $resourceFile->getOriginalName();
    if (empty($title)) {
        $title = $document->getTitle();
    }

    return [
        'filePath' => $filePath,
        'title' => $title,
        'documentKey' => (string) $docId,
        'resourceFileId' => (int) $resourceFile->getId(),
        'resourceNodeId' => (int) $resourceNode->getId(),
        'entityManager' => $entityManager,
    ];
}

/**
 * Update DB metadata after a physical save.
 */
function syncResolvedDocumentMetadata(array $resolved): void
{
    $entityManager = $resolved['entityManager'] ?? null;
    $resourceFileId = (int) ($resolved['resourceFileId'] ?? 0);
    $resourceNodeId = (int) ($resolved['resourceNodeId'] ?? 0);
    $filePath = (string) ($resolved['filePath'] ?? '');

    if (null === $entityManager || $resourceFileId <= 0 || '' === $filePath || !file_exists($filePath)) {
        return;
    }

    $size = (int) filesize($filePath);
    $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';

    try {
        $connection = $entityManager->getConnection();

        $connection->executeStatement(
            'UPDATE resource_file SET size = :size, mime_type = :mime_type WHERE id = :id',
            [
                'size' => $size,
                'mime_type' => $mimeType,
                'id' => $resourceFileId,
            ]
        );

        if ($resourceNodeId > 0) {
            $connection->executeStatement(
                'UPDATE resource_node SET updated_at = NOW() WHERE id = :id',
                [
                    'id' => $resourceNodeId,
                ]
            );
        }
    } catch (Throwable $e) {
        error_log('ONLYOFFICE CALLBACK: WARNING - Metadata sync failed: '.$e->getMessage());
    }
}

/**
 * Try to validate document server JWT, but keep URL hash as the primary routing token.
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
    } catch (Throwable $e) {
        return ['__error' => '403 Access denied'];
    }
}

/**
 * Extract the updated file URL from callback body or decoded payload.
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
 * Read Bearer token from callback request headers.
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
 * Resolve Doctrine entity manager from Symfony container.
 */
function getEntityManager()
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            $doctrine = Container::$container->get('doctrine');

            return $doctrine->getManager();
        }
    } catch (Throwable $e) {
        error_log('ONLYOFFICE CALLBACK: WARNING - Doctrine container access failed: '.$e->getMessage());
    }

    if (class_exists('Database') && method_exists('Database', 'getManager')) {
        try {
            return Database::getManager();
        } catch (Throwable $e) {
            error_log('ONLYOFFICE CALLBACK: WARNING - Database::getManager failed: '.$e->getMessage());
        }
    }

    return null;
}

/**
 * Resolve Vich storage service from Symfony container.
 */
function getVichStorage()
{
    try {
        if (isset(Container::$container) && null !== Container::$container) {
            return Container::$container->get('vich_uploader.storage');
        }
    } catch (Throwable $e) {
        error_log('ONLYOFFICE CALLBACK: WARNING - Vich storage access failed: '.$e->getMessage());
    }

    return null;
}
