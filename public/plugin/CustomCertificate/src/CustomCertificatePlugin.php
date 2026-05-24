<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Plugin class for the CustomCertificate plugin.
 *
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>
 */
class CustomCertificatePlugin extends Plugin
{
    const TABLE_CUSTOMCERTIFICATE = 'plugin_customcertificate';
    public $isCoursePlugin = true;

    // When creating a new course this settings are added to the course
    public $course_settings = [
        [
            'name' => 'customcertificate_course_enable',
            'type' => 'checkbox',
        ],
        [
            'name' => 'use_certificate_default',
            'type' => 'checkbox',
        ],
    ];

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Jose Angel Ruiz - NoSoloRed (original author), Julio Montoya',
            []
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return CustomCertificatePlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        //Installing course settings
        $this->install_course_fields_in_all_courses();

        $tablesToBeCompared = [self::TABLE_CUSTOMCERTIFICATE];
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->createSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH).'CustomCertificate/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses();

        $tablesToBeDeleted = [self::TABLE_CUSTOMCERTIFICATE];
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    /**
     * This method update the previous plugin tables.
     */
    public function update()
    {
        $oldCertificateTable = 'gradebook_certificate_alternative';
        $legacyBase = self::getLegacyCertificateImageSystemBasePath();
        if (1 == Database::num_rows(Database::query("SHOW TABLES LIKE '$oldCertificateTable'"))) {
            $sql = "SELECT * FROM $oldCertificateTable";
            $res = Database::query($sql);
            while ($row = Database::fetch_assoc($res)) {
                $pathOrigin = $legacyBase.$row['id'].'/';
                $params = [
                    'access_url_id' => api_get_current_access_url_id(),
                    'c_id' => $row['c_id'],
                    'session_id' => $row['session_id'],
                    'content_course' => $row['content_course'],
                    'contents_type' => intval($row['contents_type']),
                    'contents' => $row['contents'],
                    'date_change' => intval($row['date_change']),
                    'date_start' => $row['date_start'],
                    'date_end' => $row['date_end'],
                    'place' => $row['place'],
                    'type_date_expediction' => intval($row['type_date_expediction']),
                    'day' => $row['day'],
                    'month' => $row['month'],
                    'year' => $row['year'],
                    'logo_left' => $row['logo_left'],
                    'logo_center' => $row['logo_center'],
                    'logo_right' => $row['logo_right'],
                    'seal' => $row['seal'],
                    'signature1' => $row['signature1'],
                    'signature2' => $row['signature2'],
                    'signature3' => $row['signature3'],
                    'signature4' => $row['signature4'],
                    'signature_text1' => $row['signature_text1'],
                    'signature_text2' => $row['signature_text2'],
                    'signature_text3' => $row['signature_text3'],
                    'signature_text4' => $row['signature_text4'],
                    'background' => $row['background'],
                    'margin_left' => intval($row['margin']),
                    'margin_right' => 0,
                    'certificate_default' => 0,
                ];

                $certificateId = Database::insert(self::TABLE_CUSTOMCERTIFICATE, $params);

                // Image manager
                $imgList = [
                    'logo_left',
                    'logo_center',
                    'logo_right',
                    'seal',
                    'signature1',
                    'signature2',
                    'signature3',
                    'signature4',
                    'background',
                ];
                foreach ($imgList as $value) {
                    if (!empty($row[$value])) {
                        $storedPath = self::storeLocalCertificateImage(
                            $pathOrigin.$row[$value],
                            $certificateId,
                            $row[$value]
                        );

                        if (!empty($storedPath)) {
                            Database::update(
                                self::TABLE_CUSTOMCERTIFICATE,
                                [$value => $storedPath],
                                ['id = ?' => $certificateId]
                            );
                        }
                    }
                }

                if (1 == $row['certificate_default']) {
                    $params['c_id'] = 0;
                    $params['session_id'] = 0;
                    $params['certificate_default'] = 1;
                    $certificateId = Database::insert(self::TABLE_CUSTOMCERTIFICATE, $params);
                    $pathOrigin = $legacyBase.'default/';
                    foreach ($imgList as $value) {
                        if (!empty($row[$value])) {
                            $storedPath = self::storeLocalCertificateImage(
                                $pathOrigin.$row[$value],
                                $certificateId,
                                $row[$value]
                            );

                            if (!empty($storedPath)) {
                                Database::update(
                                    self::TABLE_CUSTOMCERTIFICATE,
                                    [$value => $storedPath],
                                    ['id = ?' => $certificateId]
                                );
                            }
                        }
                    }
                }
            }

            $sql = "DROP TABLE IF EXISTS $oldCertificateTable";
            Database::query($sql);

            echo get_lang('MessageUpdate');
        }
    }

    /**
     * By default new icon is invisible.
     *
     * @return bool
     */
    public function isIconVisibleByDefault()
    {
        return false;
    }

    public const STORAGE_DIR = 'CustomCertificate/';
    public const CERTIFICATE_IMAGE_DIR = 'certificates/';

    /**
     * Return the internal Flysystem path for a plugin storage entry.
     */
    public static function getStoragePath($relativePath = '')
    {
        $relativePath = self::sanitizeStoredImagePath($relativePath);

        if ('' === $relativePath) {
            return rtrim(self::STORAGE_DIR, '/');
        }

        return self::STORAGE_DIR.$relativePath;
    }

    /**
     * Return the internal Flysystem path for a certificate image stored in DB.
     */
    public static function getCertificateImageStoragePath($imagePath)
    {
        $imagePath = self::sanitizeStoredImagePath($imagePath);

        if ('' === $imagePath) {
            return '';
        }

        return self::getStoragePath(self::CERTIFICATE_IMAGE_DIR.$imagePath);
    }

    /**
     * Ensure a plugin storage subdirectory exists and return its Flysystem path.
     */
    public static function ensureUploadDirectory($relativePath = '')
    {
        $relativePath = self::sanitizeStoredImagePath($relativePath);
        $directoryPath = self::getStoragePath($relativePath);
        $filesystem = Container::getPluginsFileSystem();

        if (!$filesystem->directoryExists($directoryPath)) {
            $filesystem->createDirectory($directoryPath);
        }

        return $directoryPath;
    }

    /**
     * Store an uploaded certificate image in Chamilo 2 plugin storage.
     */
    public static function storeUploadedCertificateImage(array $file, int $certificateId, string $field)
    {
        if (empty($file['tmp_name']) || UPLOAD_ERR_OK !== (int) $file['error']) {
            return null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $allowedExtensions = api_get_supported_image_extensions(false);
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        if (false === @getimagesize($file['tmp_name'])) {
            return null;
        }

        $certificateId = (int) $certificateId;
        if ($certificateId <= 0) {
            return null;
        }

        $field = api_replace_dangerous_char($field);
        $originalName = api_replace_dangerous_char(pathinfo((string) $file['name'], PATHINFO_FILENAME));

        if ('' === $originalName) {
            $originalName = $field;
        }

        $fileName = $field.'_'.uniqid('', true).'_'.$originalName.'.'.$extension;
        $storedRelativePath = $certificateId.'/'.$fileName;
        $storageDirectory = self::CERTIFICATE_IMAGE_DIR.$certificateId.'/';
        $storagePath = self::getStoragePath($storageDirectory.$fileName);

        self::ensureUploadDirectory($storageDirectory);

        $stream = fopen($file['tmp_name'], 'rb');

        if (false === $stream) {
            return null;
        }

        try {
            Container::getPluginsFileSystem()->writeStream($storagePath, $stream);
        } finally {
            fclose($stream);
        }

        return $storedRelativePath;
    }

    /**
     * Store a local legacy certificate image in Chamilo 2 plugin storage.
     */
    public static function storeLocalCertificateImage($sourcePath, int $certificateId, $fileName)
    {
        $sourcePath = (string) $sourcePath;
        $fileName = api_replace_dangerous_char(basename((string) $fileName));

        if (!is_file($sourcePath) || '' === $fileName || $certificateId <= 0) {
            return null;
        }

        $storageDirectory = self::CERTIFICATE_IMAGE_DIR.$certificateId.'/';
        $storagePath = self::getStoragePath($storageDirectory.$fileName);

        self::ensureUploadDirectory($storageDirectory);

        $stream = fopen($sourcePath, 'rb');

        if (false === $stream) {
            return null;
        }

        try {
            Container::getPluginsFileSystem()->writeStream($storagePath, $stream);
        } finally {
            fclose($stream);
        }

        return $certificateId.'/'.$fileName;
    }

    /**
     * Build a protected image URL from the relative path stored in DB.
     */
    public static function getCertificateImageUrl($imagePath)
    {
        $imagePath = self::sanitizeStoredImagePath($imagePath);

        if ('' === $imagePath) {
            return '';
        }

        $storagePath = self::getCertificateImageStoragePath($imagePath);

        if ('' !== $storagePath && Container::getPluginsFileSystem()->fileExists($storagePath)) {
            return api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/src/file.php?path='.
                rawurlencode(self::CERTIFICATE_IMAGE_DIR.$imagePath);
        }

        $legacySystemBasePath = self::getLegacyCertificateImageSystemBasePath();
        if (is_file($legacySystemBasePath.$imagePath)) {
            return self::getLegacyCertificateImageWebBasePath().$imagePath;
        }

        return '';
    }

    /**
     * Build a PDF-safe image source. Data URIs avoid exposing private storage paths
     * and do not depend on HTTP callbacks from mPDF.
     */
    public static function getCertificateImageSource($imagePath)
    {
        $imagePath = self::sanitizeStoredImagePath($imagePath);

        if ('' === $imagePath) {
            return '';
        }

        $storagePath = self::getCertificateImageStoragePath($imagePath);
        $filesystem = Container::getPluginsFileSystem();

        if ('' !== $storagePath && $filesystem->fileExists($storagePath)) {
            $content = $filesystem->read($storagePath);
            $mimeType = $filesystem->mimeType($storagePath) ?: 'image/png';

            return 'data:'.$mimeType.';base64,'.base64_encode($content);
        }

        $legacySystemBasePath = self::getLegacyCertificateImageSystemBasePath();
        if (is_file($legacySystemBasePath.$imagePath)) {
            return self::getLegacyCertificateImageWebBasePath().$imagePath;
        }

        return '';
    }

    /**
     * Delete a stored certificate image from Chamilo 2 plugin storage or legacy storage.
     */
    public static function deleteUploadedCertificateFile($imagePath)
    {
        $imagePath = self::sanitizeStoredImagePath($imagePath);

        if ('' === $imagePath) {
            return false;
        }

        $storagePath = self::getCertificateImageStoragePath($imagePath);
        $filesystem = Container::getPluginsFileSystem();

        if ('' !== $storagePath && $filesystem->fileExists($storagePath)) {
            $filesystem->delete($storagePath);

            return true;
        }

        $legacyPath = self::getLegacyCertificateImageSystemBasePath().$imagePath;

        if (is_file($legacyPath)) {
            return @unlink($legacyPath);
        }

        return false;
    }

    /**
     * Output a stored plugin file through a controlled legacy endpoint.
     */
    public static function outputStoredFile($relativePath)
    {
        $relativePath = self::sanitizeStoredImagePath($relativePath);

        if ('' === $relativePath || 0 !== strpos($relativePath, self::CERTIFICATE_IMAGE_DIR)) {
            http_response_code(404);
            exit;
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $allowedExtensions = api_get_supported_image_extensions(false);

        if (!in_array($extension, $allowedExtensions, true)) {
            http_response_code(403);
            exit;
        }

        $storagePath = self::getStoragePath($relativePath);
        $filesystem = Container::getPluginsFileSystem();

        if ($filesystem->fileExists($storagePath)) {
            $mimeType = $filesystem->mimeType($storagePath) ?: 'application/octet-stream';
            $fileSize = $filesystem->fileSize($storagePath);
            $stream = $filesystem->readStream($storagePath);

            header('Content-Type: '.$mimeType);
            header('Content-Length: '.$fileSize);
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');

            fpassthru($stream);
            fclose($stream);
            exit;
        }

        $legacyRelativePath = substr($relativePath, strlen(self::CERTIFICATE_IMAGE_DIR));
        $legacyPath = self::getLegacyCertificateImageSystemBasePath().$legacyRelativePath;

        if (is_file($legacyPath)) {
            $mimeType = mime_content_type($legacyPath) ?: 'application/octet-stream';

            header('Content-Type: '.$mimeType);
            header('Content-Length: '.filesize($legacyPath));
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');

            readfile($legacyPath);
            exit;
        }

        http_response_code(404);
        exit;
    }

    /**
     * Return the previous public upload directory used by older plugin builds.
     */
    public static function getLegacyCertificateImageSystemBasePath()
    {
        return api_get_path(SYS_PUBLIC_PATH).'upload/certificates/';
    }

    /**
     * Return the previous public upload URL used by older plugin builds.
     */
    public static function getLegacyCertificateImageWebBasePath()
    {
        return api_get_path(WEB_PUBLIC_PATH).'upload/certificates/';
    }

    /**
     * Normalize DB-stored image paths and reject traversal or absolute paths.
     */
    public static function sanitizeStoredImagePath($path)
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = ltrim($path, '/');

        if ('' === $path || false !== strpos($path, "\0")) {
            return '';
        }

        if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return '';
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            return '';
        }

        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return '';
            }
        }

        return $path;
    }


    /**
     * Check hidden LP/category status from the legacy c_item_property table when it still exists.
     *
     * Chamilo 2 installations may not have this legacy table anymore, so this method must be optional.
     */
    public static function isLegacyItemPropertyHidden($tool, $ref, $sessionId)
    {
        $tool = (string) $tool;
        $ref = (int) $ref;
        $sessionId = (int) $sessionId;

        if ('' === $tool || $ref < 0) {
            return false;
        }

        try {
            $schemaManager = Database::getManager()->getConnection()->createSchemaManager();
            if (!$schemaManager->tablesExist(['c_item_property'])) {
                return false;
            }
        } catch (Exception $exception) {
            return false;
        }

        $table = 'c_item_property';
        $tool = Database::escape_string($tool);

        $sql = "SELECT 1
                FROM $table
                WHERE tool = '$tool'
                  AND ref = $ref
                  AND visibility = 0
                  AND (session_id = $sessionId OR session_id IS NULL)
                LIMIT 1";

        $result = Database::query($sql);

        return false !== $result && Database::num_rows($result) > 0;
    }


    /**
     * Check whether the plugin is enabled and the current course is configured
     * to use a custom certificate template.
     */
    public static function isEnabledForCourse($courseInfo)
    {
        if (!self::create()->isEnabled(true)) {
            return false;
        }

        if (empty($courseInfo) || !is_array($courseInfo)) {
            return false;
        }

        return 1 == api_get_course_setting('customcertificate_course_enable', $courseInfo) ||
            1 == api_get_course_setting('use_certificate_default', $courseInfo);
    }

    /**
     * Return the active template for a course or its default fallback.
     */
    public static function getActiveCertificateTemplate($courseId, $sessionId, $accessUrlId, $courseInfo)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $accessUrlId = !empty($accessUrlId) ? (int) $accessUrlId : api_get_current_access_url_id();

        if (!self::isEnabledForCourse($courseInfo)) {
            return [];
        }

        $enableCourse = 1 == api_get_course_setting('customcertificate_course_enable', $courseInfo);
        $useDefault = 1 == api_get_course_setting('use_certificate_default', $courseInfo);

        if ($enableCourse) {
            $infoCertificate = self::getInfoCertificate($courseId, $sessionId, $accessUrlId);

            if (!empty($infoCertificate)) {
                return $infoCertificate;
            }
        }

        if ($useDefault || $enableCourse) {
            $infoCertificate = self::getInfoCertificateDefault($accessUrlId);

            if (!empty($infoCertificate)) {
                return $infoCertificate;
            }
        }

        return [];
    }

    /**
     * Generate the HTML certificate body using the CustomCertificate template.
     *
     * This method is intentionally side-effect free: it only renders the HTML.
     * Gradebook is still responsible for creating/updating the issued
     * certificate resource and the gradebook_certificate row.
     */
    public static function generateHtmlCertificateForUser(
        $courseId,
        $courseCode,
        $sessionId,
        $studentId,
        $accessUrlId = 0,
        $categoryId = 0,
        $wrapHtml = true
    ) {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;
        $categoryId = (int) $categoryId;
        $accessUrlId = !empty($accessUrlId) ? (int) $accessUrlId : api_get_current_access_url_id();

        if ($courseId <= 0 || $studentId <= 0) {
            return '';
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            $courseInfo = api_get_course_info((string) $courseCode);
        }

        if (empty($courseInfo)) {
            return '';
        }

        $courseCode = !empty($courseCode) ? (string) $courseCode : (string) ($courseInfo['code'] ?? '');
        $infoCertificate = self::getActiveCertificateTemplate($courseId, $sessionId, $accessUrlId, $courseInfo);

        if (empty($infoCertificate)) {
            return '';
        }

        $plugin = self::create();
        $sessionInfo = [];
        if ($sessionId > 0) {
            $sessionInfo = SessionManager::fetch($sessionId);
        }

        $infoCertificate = array_merge([
            'margin_left' => 0,
            'margin_right' => 0,
            'logo_left' => '',
            'logo_center' => '',
            'logo_right' => '',
            'seal' => '',
            'signature1' => '',
            'signature2' => '',
            'signature3' => '',
            'signature4' => '',
            'signature_text1' => '',
            'signature_text2' => '',
            'signature_text3' => '',
            'signature_text4' => '',
            'background' => '',
            'content_course' => '',
            'contents_type' => 3,
            'contents' => '',
            'date_change' => 2,
            'date_start' => '',
            'date_end' => '',
            'type_date_expediction' => 1,
            'place' => '',
            'day' => '',
            'month' => '',
            'year' => '',
        ], $infoCertificate);

        $workSpace = max(10, (int) (297 - (int) $infoCertificate['margin_left'] - (int) $infoCertificate['margin_right']));
        $widthCell = max(1, (int) ($workSpace / 6));
        $htmlText = '';

        if ($wrapHtml) {
            $htmlText .= '<html>';
            $htmlText .= '<link rel="stylesheet" type="text/css" href="'.
                api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/resources/css/certificate.css">';
            $htmlText .= '<link rel="stylesheet" type="text/css" href="'.
                api_get_path(WEB_CSS_PATH).'document.css">';
            $htmlText .= '<body>';
        }

        if (empty($infoCertificate['background'])) {
            $htmlText .= '<div class="caraA" style="page-break-before:always; margin:0; padding:0;">';
        } else {
            $urlBackground = self::getCertificateImageSource($infoCertificate['background']);
            $htmlText .= '<div class="caraA" style="background-image:url('.$urlBackground.'); background-image-resize:6; margin:0; padding:0;">';
        }

        $hasLogoRow = !empty($infoCertificate['logo_left']) ||
            !empty($infoCertificate['logo_center']) ||
            !empty($infoCertificate['logo_right']);

        if ($hasLogoRow) {
            $logoLeft = !empty($infoCertificate['logo_left'])
                ? '<img style="max-height:150px; max-width:'.(2 * $widthCell).'mm;" src="'.
                    self::getCertificateImageSource($infoCertificate['logo_left']).'" />'
                : '';
            $logoCenter = !empty($infoCertificate['logo_center'])
                ? '<img style="max-height:150px; max-width:'.(int) ($workSpace - (2 * $widthCell)).'mm;" src="'.
                    self::getCertificateImageSource($infoCertificate['logo_center']).'" />'
                : '';
            $logoRight = !empty($infoCertificate['logo_right'])
                ? '<img style="max-height:150px; max-width:'.(2 * $widthCell).'mm;" src="'.
                    self::getCertificateImageSource($infoCertificate['logo_right']).'" />'
                : '';

            $htmlText .= '<table width="'.$workSpace.'mm" style="margin-left:'.
                (int) $infoCertificate['margin_left'].'mm; margin-right:'.
                (int) $infoCertificate['margin_right'].'mm;" border="0">';
            $htmlText .= '<tr>';
            $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm" class="logo">'.$logoLeft.'</td>';
            $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm; text-align:center;" class="logo">'.$logoCenter.'</td>';
            $htmlText .= '<td style="width:'.(int) ($workSpace / 3).'mm; text-align:right;" class="logo">'.$logoRight.'</td>';
            $htmlText .= '</tr>';
            $htmlText .= '</table>';
        }

        $allUserInfo = DocumentManager::get_all_info_to_certificate(
            $studentId,
            $courseId,
            $sessionId,
            false
        );

        $myContentHtml = Security::remove_XSS((string) $infoCertificate['content_course']);
        $myContentHtml = str_replace(chr(13).chr(10).chr(13).chr(10), chr(13).chr(10), $myContentHtml);

        if (!empty($allUserInfo[0]) && !empty($allUserInfo[1])) {
            $tags = $allUserInfo[0];
            $values = $allUserInfo[1];

            $score = self::getGradebookScoreForUser($studentId, $categoryId);
            if ('' !== $score) {
                $gradeIndex = array_search('((gradebook_grade))', $tags, true);
                if (false !== $gradeIndex && empty($values[$gradeIndex])) {
                    $values[$gradeIndex] = $score;
                }
            }

            $myContentHtml = str_replace($tags, $values, $myContentHtml);
        }

        $startDate = '';
        $endDate = '';
        switch ((int) $infoCertificate['date_change']) {
            case 0:
                if (!empty($sessionInfo['access_start_date'])) {
                    $startDate = date('d/m/Y', strtotime(api_get_local_time($sessionInfo['access_start_date'])));
                }
                if (!empty($sessionInfo['access_end_date'])) {
                    $endDate = date('d/m/Y', strtotime(api_get_local_time($sessionInfo['access_end_date'])));
                }
                break;
            case 1:
                if (!empty($infoCertificate['date_start'])) {
                    $startDate = date('d/m/Y', strtotime((string) $infoCertificate['date_start']));
                }
                if (!empty($infoCertificate['date_end'])) {
                    $endDate = date('d/m/Y', strtotime((string) $infoCertificate['date_end']));
                }
                break;
        }

        $myContentHtml = str_replace('((start_date))', $startDate, $myContentHtml);
        $myContentHtml = str_replace('((end_date))', $endDate, $myContentHtml);

        $dateExpediction = self::buildDateExpedictionText($infoCertificate, $sessionInfo, $plugin, $allUserInfo[1] ?? []);
        $myContentHtml = str_replace('((date_expediction))', $dateExpediction, $myContentHtml);

        $myContentHtml = strip_tags(
            $myContentHtml,
            '<p><b><strong><table><tr><td><th><tbody><span><i><li><ol><ul><dd><dt><dl><br><hr><img><a><div><h1><h2><h3><h4><h5><h6>'
        );

        $htmlText .= '<div style="min-height:420px; width:'.$workSpace.'mm; margin-left:'.
            (int) $infoCertificate['margin_left'].'mm; margin-right:'.
            (int) $infoCertificate['margin_right'].'mm;">';
        $htmlText .= $myContentHtml;
        $htmlText .= '</div>';

        $hasSignatures = !empty($infoCertificate['signature_text1']) ||
            !empty($infoCertificate['signature_text2']) ||
            !empty($infoCertificate['signature_text3']) ||
            !empty($infoCertificate['signature_text4']) ||
            !empty($infoCertificate['signature1']) ||
            !empty($infoCertificate['signature2']) ||
            !empty($infoCertificate['signature3']) ||
            !empty($infoCertificate['signature4']) ||
            !empty($infoCertificate['seal']);

        if ($hasSignatures) {
            $htmlText .= self::buildSignaturesHtml($infoCertificate, $workSpace, $widthCell, $plugin);
        }

        $htmlText .= '</div>';

        if (3 !== (int) $infoCertificate['contents_type']) {
            $htmlText .= self::buildRearCertificateHtml($infoCertificate, $courseId, $courseCode, $sessionId, $studentId, $plugin);
        }

        if ($wrapHtml) {
            $htmlText .= '</body></html>';
        }

        return $htmlText;
    }

    private static function getGradebookScoreForUser($studentId, $categoryId)
    {
        $studentId = (int) $studentId;
        $categoryId = (int) $categoryId;

        if ($studentId <= 0 || $categoryId <= 0) {
            return '';
        }

        $table = Database::get_main_table('gradebook_certificate');
        $sql = "SELECT score_certificate
                FROM $table
                WHERE user_id = $studentId AND cat_id = $categoryId
                ORDER BY id DESC
                LIMIT 1";
        $result = Database::query($sql);

        if (false === $result || 0 === Database::num_rows($result)) {
            return '';
        }

        $row = Database::fetch_assoc($result);

        return isset($row['score_certificate']) ? (string) $row['score_certificate'] : '';
    }

    private static function buildDateExpedictionText(array $infoCertificate, array $sessionInfo, $plugin, array $replacementValues)
    {
        $dateExpediction = '';

        if (3 === (int) $infoCertificate['type_date_expediction']) {
            return $dateExpediction;
        }

        $dateExpediction .= $plugin->get_lang('ExpedictionIn').' '.Security::remove_XSS((string) $infoCertificate['place']);

        if (1 === (int) $infoCertificate['type_date_expediction']) {
            return $dateExpediction.$plugin->get_lang('to').api_format_date(time(), DATE_FORMAT_LONG);
        }

        if (2 === (int) $infoCertificate['type_date_expediction']) {
            $dateFormat = $plugin->get_lang('formatDownloadDate');

            if (!empty($infoCertificate['day']) && !empty($infoCertificate['month']) && !empty($infoCertificate['year'])) {
                return $dateExpediction.sprintf(
                    $dateFormat,
                    $infoCertificate['day'],
                    $infoCertificate['month'],
                    $infoCertificate['year']
                );
            }

            return $dateExpediction.sprintf(
                $dateFormat,
                '......',
                '....................',
                '............'
            );
        }

        if (4 === (int) $infoCertificate['type_date_expediction']) {
            return $dateExpediction.$plugin->get_lang('to').($replacementValues[9] ?? '');
        }

        if (!empty($sessionInfo['access_end_date'])) {
            $dateInfo = api_get_local_time($sessionInfo['access_end_date']);

            return $dateExpediction.$plugin->get_lang('to').api_format_date($dateInfo, DATE_FORMAT_LONG);
        }

        return $dateExpediction;
    }

    private static function buildSignaturesHtml(array $infoCertificate, $workSpace, $widthCell, $plugin)
    {
        $html = '<table width="'.(int) $workSpace.'mm" style="margin-left:'.
            (int) $infoCertificate['margin_left'].'mm; margin-right:'.
            (int) $infoCertificate['margin_right'].'mm;" border="0">';

        $html .= '<tr>';
        for ($i = 1; $i <= 4; $i++) {
            $html .= '<td colspan="2" class="seals" style="width:'.(int) $widthCell.'mm">'.
                (!empty($infoCertificate['signature_text'.$i]) ? Security::remove_XSS((string) $infoCertificate['signature_text'.$i]) : '').
                '</td>';
        }
        $html .= '<td colspan="4" class="seals" style="width:'.(2 * (int) $widthCell).'mm">'.
            (!empty($infoCertificate['seal']) ? $plugin->get_lang('Seal') : '').
            '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        for ($i = 1; $i <= 4; $i++) {
            $html .= '<td colspan="2" class="logo-seals" style="width:'.(int) $widthCell.'mm">';
            if (!empty($infoCertificate['signature'.$i])) {
                $html .= '<img style="max-height:100px; max-width:'.(int) $widthCell.'mm;" src="'.
                    self::getCertificateImageSource($infoCertificate['signature'.$i]).'" />';
            }
            $html .= '</td>';
        }
        $html .= '<td colspan="4" class="logo-seals" style="width:'.(2 * (int) $widthCell).'mm">';
        if (!empty($infoCertificate['seal'])) {
            $html .= '<img style="max-height:100px; max-width:'.(2 * (int) $widthCell).'mm;" src="'.
                self::getCertificateImageSource($infoCertificate['seal']).'" />';
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }

    private static function buildRearCertificateHtml(array $infoCertificate, $courseId, $courseCode, $sessionId, $studentId, $plugin)
    {
        $html = '<div class="caraB" style="page-break-before:always; margin:0; padding:0;">';

        if (0 === (int) $infoCertificate['contents_type']) {
            $courseDescription = new CourseDescription();
            $contentDescription = $courseDescription->get_data_by_description_type(3, (int) $courseId, 0);
            $domd = new DOMDocument();
            libxml_use_internal_errors(true);
            if (isset($contentDescription['description_content'])) {
                $domd->loadHTML($contentDescription['description_content']);
            }
            libxml_use_internal_errors(false);
            $domx = new DOMXPath($domd);

            foreach ($domx->query('//li[@style]') as $item) {
                $item->removeAttribute('style');
            }

            foreach ($domx->query('//span[@style]') as $item) {
                $item->removeAttribute('style');
            }

            $html .= self::filterIndexHtml((string) $domd->saveHTML());
        }

        if (1 === (int) $infoCertificate['contents_type']) {
            $items = [];
            $categoriesTempList = learnpath::getCategories((int) $courseId);
            $categoryTest = new \Chamilo\CourseBundle\Entity\CLpCategory();
            $categoryTest->setId(0);
            $categoryTest->setTitle($plugin->get_lang('WithOutCategory'));
            $categories = [$categoryTest];

            if (!empty($categoriesTempList)) {
                $categories = array_merge($categories, $categoriesTempList);
            }

            foreach ($categories as $item) {
                $categoryId = $item->getId();

                if (!learnpath::categoryIsVisibleForStudent($item, api_get_user_entity((int) $studentId))) {
                    continue;
                }

                if (self::isLegacyItemPropertyHidden('learnpath_category', $categoryId, (int) $sessionId)) {
                    continue;
                }

                $list = new LearnpathList(
                    (int) $studentId,
                    $courseCode,
                    (int) $sessionId,
                    null,
                    false,
                    $categoryId
                );

                $flatList = $list->get_flat_list();

                if (empty($flatList)) {
                    continue;
                }

                if (count($categories) > 1 && $item->getTitle() != $plugin->get_lang('WithOutCategory')) {
                    $items[] = '<h4 style="margin:0">'.$item->getTitle().'</h4>';
                }

                foreach ($flatList as $learnpath) {
                    $lpId = $learnpath['lp_old_id'];
                    if (self::isLegacyItemPropertyHidden('learnpath', $lpId, (int) $sessionId)) {
                        continue;
                    }
                    $items[] = $learnpath['lp_name'].'<br>';
                }
                $items[] = '<br>';
            }

            if (count($items) > 0) {
                $html .= '<table width="100%" class="contents-learnpath"><tr><td>';
                $i = 0;
                foreach ($items as $value) {
                    if (50 === $i) {
                        $html .= '</td><td>';
                    }
                    $html .= $value;
                    $i++;
                }
                $html .= '</td></tr></table>';
            }
        }

        if (2 === (int) $infoCertificate['contents_type']) {
            $html .= '<table width="100%" class="contents-learnpath"><tr><td>';
            $html .= strip_tags(
                (string) $infoCertificate['contents'],
                '<p><b><strong><table><tr><td><th><span><i><li><ol><ul><dd><dt><dl><br><hr><img><a><div><h1><h2><h3><h4><h5><h6>'
            );
            $html .= '</td></tr></table>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function filterIndexHtml($index)
    {
        $txt = strip_tags((string) $index, '<b><strong><i>');
        $txt = str_replace(chr(13).chr(10).chr(13).chr(10), chr(13).chr(10), $txt);
        $lines = explode(chr(13).chr(10), $txt);

        $text1 = '';
        for ($x = 0; $x < 47; $x++) {
            if (isset($lines[$x])) {
                $text1 .= $lines[$x].chr(13).chr(10);
            }
        }

        $text2 = '';
        for ($x = 47; $x < 94; $x++) {
            if (isset($lines[$x])) {
                $text2 .= $lines[$x].chr(13).chr(10);
            }
        }

        return '<table width="100%"><tr><td style="width:50%;vertical-align:top;padding-left:15px; font-size:12px;">'.
            str_replace(chr(13).chr(10), '<br/>', $text1).
            '</td><td style="vertical-align:top; font-size:12px;">'.
            str_replace(chr(13).chr(10), '<br/>', $text2).
            '</td></tr></table>';
    }


    /**
     * Get certificate data.
     *
     * @param int $id     The certificate
     * @param int $userId
     *
     * @return array
     */
    public static function getCertificateData($id, $userId)
    {
        $id = (int) $id;
        $userId = (int) $userId;

        if (empty($id) || empty($userId)) {
            return [];
        }

        $certificateTable = Database::get_main_table('gradebook_certificate');
        $categoryTable = Database::get_main_table('gradebook_category');

        $sql = "SELECT
                    cer.user_id AS user_id,
                    cer.cat_id AS cat_id,
                    cat.c_id AS course_id,
                    cat.session_id AS session_id
                FROM $certificateTable cer
                INNER JOIN $categoryTable cat
                    ON cer.cat_id = cat.id
                WHERE cer.id = $id
                  AND cer.user_id = $userId
                LIMIT 1";

        try {
            $rs = Database::query($sql);
        } catch (Throwable $exception) {
            error_log('[CustomCertificatePlugin::getCertificateData] '.$exception->getMessage());

            return [];
        }

        if (false === $rs || 0 === Database::num_rows($rs)) {
            return [];
        }

        $row = Database::fetch_assoc($rs);
        $courseId = isset($row['course_id']) ? (int) $row['course_id'] : 0;
        $sessionId = isset($row['session_id']) ? (int) $row['session_id'] : 0;
        $categoryId = isset($row['cat_id']) ? (int) $row['cat_id'] : 0;
        $userId = isset($row['user_id']) ? (int) $row['user_id'] : $userId;

        if ($courseId <= 0) {
            return [];
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return [];
        }

        $courseCode = (string) ($courseInfo['code'] ?? '');
        if ('' === $courseCode) {
            return [];
        }

        if (self::isEnabledForCourse($courseInfo)) {
            return [
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'cat_id' => $categoryId,
            ];
        }

        return [];
    }

    /**
     * Check if it redirects.
     *
     * @param certificate $certificate
     * @param int         $certId
     * @param int         $userId
     */
    public static function redirectCheck($certificate, $certId, $userId)
    {
        $certId = (int) $certId;
        $userId = !empty($userId) ? $userId : api_get_user_id();

        if (self::create()->isEnabled(true)) {
            $infoCertificate = self::getCertificateData($certId, $userId);
            if (!empty($infoCertificate)) {
                if ($certificate->user_id == api_get_user_id() && !empty($certificate->certificate_data)) {
                    $certificateId = $certificate->certificate_data['id'];
                    $extraFieldValue = new ExtraFieldValue('user_certificate');
                    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $certificateId,
                        'downloaded_at'
                    );
                    if (empty($value)) {
                        $params = [
                            'item_id' => $certificate->certificate_data['id'],
                            'extra_downloaded_at' => api_get_utc_datetime(),
                        ];
                        $extraFieldValue->saveFieldValues($params);
                    }
                }

                $url = api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/src/print_certificate.php'.
                    '?student_id='.$infoCertificate['user_id'].
                    '&course_code='.$infoCertificate['course_code'].
                    '&session_id='.$infoCertificate['session_id'].
                    '&cat_id='.(int) ($infoCertificate['cat_id'] ?? 0);
                header('Location: '.$url);
                exit;
            }
        }
    }

    /**
     * Get certificate info.
     *
     * @param int $courseId
     * @param int $sessionId
     * @param int $accessUrlId
     *
     * @return array
     */
    public static function getInfoCertificate($courseId, $sessionId, $accessUrlId)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $accessUrlId = !empty($accessUrlId) ? (int) $accessUrlId : 1;

        $table = Database::get_main_table(self::TABLE_CUSTOMCERTIFICATE);
        $sql = "SELECT * FROM $table
                WHERE 
                    c_id = $courseId AND 
                    session_id = $sessionId AND
                    access_url_id = $accessUrlId";
        $result = Database::query($sql);
        $resultArray = [];
        if (Database::num_rows($result) > 0) {
            $resultArray = Database::fetch_array($result);
        }

        return $resultArray;
    }

    /**
     * Get default certificate info.
     *
     * @param int $accessUrlId
     *
     * @return array
     */
    public static function getInfoCertificateDefault($accessUrlId)
    {
        $accessUrlId = !empty($accessUrlId) ? (int) $accessUrlId : 1;

        $table = Database::get_main_table(self::TABLE_CUSTOMCERTIFICATE);
        $sql = "SELECT * FROM $table
                WHERE certificate_default = 1 AND access_url_id = $accessUrlId";
        $result = Database::query($sql);
        $resultArray = [];
        if (Database::num_rows($result) > 0) {
            $resultArray = Database::fetch_array($result);
        }

        return $resultArray;
    }
}
