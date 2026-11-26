<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\JustificationDocument;
use Chamilo\CoreBundle\Entity\JustificationDocumentRelUsers;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\QueryBuilder;

class Justification extends Plugin
{
    public const TABLE_DOCUMENT = 'justification_document';
    public const TABLE_DOCUMENT_REL_USER = 'justification_document_rel_users';

    protected function __construct()
    {
        parent::__construct(
            '1.2',
            'Julio Montoya, Nicolas Ducoulombier',
            [
                'notification_to_creator_only' => 'boolean',
                'access_for_session_admin' => 'boolean',
                'default_course_id' => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function getJustification($id)
    {
        $id = (int) $id;

        $document = $this->documentRowsQueryBuilder()
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult()[0] ?? false;
        if ($document) {
            // Keep the legacy array key until all external plugin templates are updated.
            $document['name'] = $document['title'];
        }

        return $document;
    }

    public function getUserJustificationList($userId)
    {
        $userId = (int) $userId;

        $rows = $this->userJustificationRowsQueryBuilder()
            ->where('IDENTITY(j.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        return array_map([$this, 'toLegacyUserJustificationRow'], $rows);
    }

    public function getUserJustification($id)
    {
        $id = (int) $id;

        $row = $this->userJustificationRowsQueryBuilder()
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        return null === $row ? false : $this->toLegacyUserJustificationRow($row);
    }

    public function getList()
    {
        $documents = $this->documentRowsQueryBuilder()
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($documents as &$document) {
            // Keep the legacy array key until all external plugin templates are updated.
            $document['name'] = $document['title'];
        }
        unset($document);

        return $documents;
    }

    public function canSessionAdminsManageUsers()
    {
        return 'true' === api_get_plugin_setting('justification', 'access_for_session_admin');
    }

    /**
     * Whether the current user can manage the justifications of the given user: platform admins and, when the
     * plugin allows it, session admins, only for the users of the current access URL and, for a session admin
     * restricted by prevent_session_admins_to_manage_all_users, only for the users they created.
     */
    public function canManageUserJustifications(int $userId): bool
    {
        $isPlatformAdmin = api_is_platform_admin();
        $isSessionAdmin = !$isPlatformAdmin && $this->canSessionAdminsManageUsers() && api_is_session_admin();

        if ($userId <= 0 || (!$isPlatformAdmin && !$isSessionAdmin)) {
            return false;
        }

        if (api_get_multiple_access_url()
            && !UrlManager::relation_url_user_exist($userId, api_get_current_access_url_id())
        ) {
            return false;
        }

        if ($isSessionAdmin && 'true' === api_get_setting('prevent_session_admins_to_manage_all_users')) {
            $user = api_get_user_entity($userId);

            return null !== $user && $user->getCreatorId() === api_get_user_id();
        }

        return true;
    }

    public function getDefaultCourseId()
    {
        $courseId = $this->get('default_course_id');

        if (empty($courseId)) {
            $courseId = api_get_setting('justification_default_course_id', 'justification');
        }

        return (int) $courseId;
    }

    public function getAdminUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'Justification/list.php';
    }


    public function renderRegion($region)
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $allowSessionAdmin = $this->canSessionAdminsManageUsers();
        if (!api_is_platform_admin() && !($allowSessionAdmin && api_is_session_admin())) {
            return '';
        }

        $url = api_get_path(WEB_PLUGIN_PATH).'Justification/list.php';
        $title = $this->get_lang('plugin_title');

        return Display::url(
            $title,
            $url,
            [
                'class' => 'block px-3 py-2 text-primary hover:underline',
                'title' => $title,
            ]
        );
    }


    public function getStorageRoot(): string
    {
        return dirname(__DIR__, 3).'/var/upload/justification';
    }

    public function getStoragePathForUser(int $userId): string
    {
        return $this->getStorageRoot().'/user_'.$userId;
    }

    public function getUserJustificationDownloadUrl(int $id): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'Justification/download.php?id='.$id;
    }

    public function getUserJustificationFileSystemPath(array $userJustification): ?string
    {
        $filePath = $userJustification['file_path'] ?? '';

        if ('' === $filePath || str_contains($filePath, '..')) {
            return null;
        }

        return $this->getStorageRoot().'/'.$filePath;
    }

    public function getUserJustificationByDocument(int $userId, int $documentId): ?array
    {
        $userId = (int) $userId;
        $documentId = (int) $documentId;

        $result = $this->userJustificationRowsQueryBuilder()
            ->where('IDENTITY(j.user) = :userId')
            ->andWhere('IDENTITY(j.justificationDocument) = :documentId')
            ->setParameter('userId', $userId)
            ->setParameter('documentId', $documentId)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        return $result ? $this->toLegacyUserJustificationRow($result) : null;
    }

    public function saveUploadedJustification(int $userId, int $documentId, array $file, ?string $manualValidityDate = null): bool
    {
        if ($userId <= 0 || $documentId <= 0 || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        $document = $this->getJustification($documentId);
        if (empty($document)) {
            return false;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return false;
        }

        $userDirectory = $this->getStoragePathForUser($userId);
        if (!is_dir($userDirectory) && !mkdir($userDirectory, api_get_permissions_for_new_directories(), true) && !is_dir($userDirectory)) {
            return false;
        }

        $safeBaseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = api_replace_dangerous_char($safeBaseName);
        $safeBaseName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $safeBaseName);
        $safeBaseName = trim((string) $safeBaseName, '._-');

        if ('' === $safeBaseName) {
            $safeBaseName = 'justification';
        }

        $storedFileName = sprintf(
            'document_%d_%s_%s.%s',
            $documentId,
            date('YmdHis'),
            bin2hex(random_bytes(4)),
            $extension
        );

        $targetPath = $userDirectory.'/'.$storedFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        @chmod($targetPath, api_get_permissions_for_new_files());

        $relativePath = 'user_'.$userId.'/'.$storedFileName;
        $validityDate = $this->resolveValidityDate($document, $manualValidityDate);

        $existing = $this->getUserJustificationByDocument($userId, $documentId);
        if ($existing) {
            $oldPath = $this->getUserJustificationFileSystemPath($existing);
            if ($oldPath && is_file($oldPath)) {
                @unlink($oldPath);
            }

            Database::update(
                self::TABLE_DOCUMENT_REL_USER,
                [
                    'file_path' => $relativePath,
                    'date_validity' => $validityDate,
                ],
                ['id = ?' => (int) $existing['id']]
            );

            return true;
        }

        Database::insert(
            self::TABLE_DOCUMENT_REL_USER,
            [
                'justification_document_id' => $documentId,
                'file_path' => $relativePath,
                'user_id' => $userId,
                'date_validity' => $validityDate,
            ]
        );

        return true;
    }

    public function deleteUserJustification(int $id): bool
    {
        $id = (int) $id;

        if ($id <= 0) {
            return false;
        }

        $userJustification = $this->getUserJustification($id);
        if (!$userJustification) {
            return false;
        }

        $filePath = $this->getUserJustificationFileSystemPath($userJustification);
        if ($filePath && is_file($filePath)) {
            @unlink($filePath);
        }

        Container::getEntityManager()->createQueryBuilder()
            ->delete(JustificationDocumentRelUsers::class, 'j')
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();

        return true;
    }

    /**
     * Every column of the document table, aliased to the snake_case keys the legacy callers read.
     */
    private function documentRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'd.id AS id',
                'd.code AS code',
                'd.title AS title',
                'd.validityDuration AS validity_duration',
                'd.comment AS comment',
                'd.dateManualOn AS date_manual_on'
            )
            ->from(JustificationDocument::class, 'd');
    }

    /**
     * Every column of the user justification table, aliased to the snake_case keys the legacy callers read.
     */
    private function userJustificationRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'j.id AS id',
                'IDENTITY(j.justificationDocument) AS justification_document_id',
                'j.filePath AS file_path',
                'IDENTITY(j.user) AS user_id',
                'j.dateValidity AS date_validity'
            )
            ->from(JustificationDocumentRelUsers::class, 'j')
            ->orderBy('j.id', 'ASC');
    }

    /**
     * The raw table row held the date as a Y-m-d string and the foreign keys as integers: keep that shape.
     */
    private function toLegacyUserJustificationRow(array $row): array
    {
        $row['date_validity'] = $row['date_validity'] instanceof DateTimeInterface
            ? $row['date_validity']->format('Y-m-d')
            : $row['date_validity'];
        $row['justification_document_id'] = null === $row['justification_document_id']
            ? null
            : (int) $row['justification_document_id'];
        $row['user_id'] = null === $row['user_id'] ? null : (int) $row['user_id'];

        return $row;
    }

    private function resolveValidityDate(array $document, ?string $manualValidityDate): string
    {
        $dateManualOn = !empty($document['date_manual_on']);

        if ($dateManualOn && !empty($manualValidityDate)) {
            $timestamp = strtotime($manualValidityDate);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }

        $duration = isset($document['validity_duration']) ? (int) $document['validity_duration'] : 0;
        if ($duration > 0) {
            return date('Y-m-d', strtotime('+'.$duration.' days'));
        }

        return date('Y-m-d');
    }

    /**
     * Install.
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_DOCUMENT." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            code TEXT NULL,
            title TEXT NULL,
            validity_duration INT,
            comment TEXT NULL,
            date_manual_on INT
        )";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_DOCUMENT_REL_USER." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            justification_document_id INT NOT NULL,
            file_path VARCHAR(255),
            user_id INT,
            date_validity DATE
        )";
        Database::query($sql);
    }

    public function uninstall()
    {
        // Drop the child table first because it can reference justification_document.
        $sql = 'DROP TABLE IF EXISTS '.self::TABLE_DOCUMENT_REL_USER;
        Database::query($sql);

        $sql = 'DROP TABLE IF EXISTS '.self::TABLE_DOCUMENT;
        Database::query($sql);
    }
}
