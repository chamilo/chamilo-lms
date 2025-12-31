<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Certificate Class
 * Generate certificates based in the gradebook tool.
 */
class Certificate extends Model
{
    public $table;
    public $columns = [
        'id',
        'cat_id',
        'score_certificate',
        'created_at',
        'path_certificate',
    ];
    /**
     * Certification data.
     */
    public $certificate_data = [];

    /**
     * Student's certification path.
     */
    public $certification_user_path = null;
    public $certification_web_user_path = null;
    public $html_file = null;
    public $qr_file = null;
    public $user_id;

    /** If true every time we enter to the certificate URL
     * we would generate a new certificate (good thing because we can edit the
     * certificate and all users will have the latest certificate bad because we.
     * load the certificate every time */
    public $force_certificate_generation = true;

    /**
     * Constructor.
     *
     * @param int  $certificate_id        ID of the certificate
     * @param int  $userId
     * @param bool $sendNotification      send message to student
     * @param bool $updateCertificateData
     * @param string $pathToCertificate
     *
     * If no ID given, take user_id and try to generate one
     */
    public function __construct(
        $certificate_id = 0,
        $userId = 0,
        $sendNotification = false,
        $updateCertificateData = true,
        $pathToCertificate = ''
    ) {
        $this->table   = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $this->user_id = !empty($userId) ? (int) $userId : api_get_user_id();

        // Load legacy row if an ID is provided
        if (!empty($certificate_id)) {
            $certificate = $this->get($certificate_id);
            if (is_array($certificate) && !empty($certificate)) {
                $this->certificate_data = $certificate;
                $this->user_id = (int) $this->certificate_data['user_id'];
            }
        }

        if (empty($this->user_id)) {
            // No user context, nothing else to do.
            return;
        }

        $certRepo   = Container::getGradeBookCertificateRepository();
        $categoryId = isset($this->certificate_data['cat_id']) ? (int) $this->certificate_data['cat_id'] : 0;

        // Try to preload an existing resource to avoid unnecessary work.
        try {
            $existing = $certRepo->getCertificateByUserId($categoryId === 0 ? null : $categoryId, $this->user_id);
            if ($existing && $existing->hasResourceNode()) {
                // Resource-first model: legacy path is not used anymore.
                $this->certification_user_path = 'resource://user_certificate';
                $this->html_file = '';
                $this->certificate_data['path_certificate'] = '';
                $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($existing);
            }
        } catch (\Throwable $e) {
            // Non-fatal; generation can still proceed if needed.
            error_log('[CERT::__construct] preload resource error: '.$e->getMessage());
        }

        // Keep original behavior: optionally generate on construct.
        if ($this->force_certificate_generation) {
            try {
                $this->generate(['certificate_path' => $pathToCertificate], $sendNotification);
                // Refresh in-memory HTML for PDF generation after generate().
                $refetched = $certRepo->getCertificateByUserId($categoryId === 0 ? null : $categoryId, $this->user_id);
                if ($refetched && $refetched->hasResourceNode()) {
                    $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($refetched);
                    $this->certification_user_path = 'resource://user_certificate';
                }
            } catch (\Throwable $e) {
                error_log('[CERT::__construct] generate-on-construct failed: '.$e->getMessage());
            }
        }

        // If still empty legacy path but we have a row, keep original fallback.
        if (
            isset($this->certificate_data) &&
            $this->certificate_data &&
            empty($this->certificate_data['path_certificate']) &&
            !$this->force_certificate_generation
        ) {
            try {
                $this->generate(['certificate_path' => $pathToCertificate], $sendNotification);
                $refetched = $certRepo->getCertificateByUserId($categoryId === 0 ? null : $categoryId, $this->user_id);
                if ($refetched && $refetched->hasResourceNode()) {
                    $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($refetched);
                    $this->certification_user_path = 'resource://user_certificate';
                }
            } catch (\Throwable $e) {
                error_log('[CERT::__construct] generate (no legacy path) failed: '.$e->getMessage());
            }
        }

        // Setting the qr and html variables
        if (
            isset($certificate_id) &&
            !empty($this->certification_user_path) &&
            isset($this->certificate_data['path_certificate']) &&
            !empty($this->certificate_data['path_certificate'])
        ) {
            // Legacy: path points to a file name; we only keep it for BC.
            $this->html_file = $this->certificate_data['path_certificate'];
        } else {
            if ('true' === api_get_setting('certificate.allow_general_certificate')) {
                // Guard: if a resource already exists, just populate file_content and exit.
                try {
                    $already = $certRepo->getCertificateByUserId(null, $this->user_id); // general certificate => null cat
                    if ($already && $already->hasResourceNode()) {
                        $this->certification_user_path = 'resource://user_certificate';
                        $this->certificate_data['path_certificate'] = '';
                        $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($already);
                        return; // Nothing else to do.
                    }
                } catch (\Throwable $e) {
                    error_log('[CERT::__construct] check-existing general cert error: '.$e->getMessage());
                }

                // General certificate
                // store as a Resource (resource_type = user_certificate) instead of PersonalFile
                $cert = null;
                try {
                    // Build HTML content (always available for PDF even if upsert fails).
                    $content = $this->generateCustomCertificate();

                    $hash     = hash('sha256', $this->user_id.$categoryId);
                    $fileName = $hash.'.html';

                    // upsertCertificateResource(catId, userId, score, htmlContent, pdfBinary?, legacyFileName?)
                    $cert = $certRepo->upsertCertificateResource(0, $this->user_id, 100.0, $content, null, $fileName);

                    // Keep legacy compatibility fields in DB if required
                    if ($updateCertificateData) {
                        $certRepo->registerUserInfoAboutCertificate(0, $this->user_id, 100.0, $fileName);
                    }

                    // Update in-memory fields for downstream consumers (PDF)
                    $this->certification_user_path = 'resource://user_certificate';
                    $this->certificate_data['path_certificate'] = $fileName;

                    // Ensure file_content is always available (avoid undefined index)
                    try {
                        $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($cert);
                    } catch (\Throwable $ignored) {
                        // Fallback: use raw generated HTML so PDF creation never crashes.
                        $this->certificate_data['file_content'] = $content;
                    }

                    // Optional: keep the legacy user-certificate metadata updated
                    $this->updateUserCertificateInfo(
                        0,
                        $this->user_id,
                        $fileName,
                        $updateCertificateData
                    );
                } catch (\Throwable $e) {
                    // Do not break the constructor; log and keep going
                    error_log('[CERT] general certificate upsert error: '.$e->getMessage());
                    // As a last resort, populate file_content with a minimal HTML to avoid PDF fatal errors
                    if (empty($this->certificate_data['file_content'])) {
                        $this->certificate_data['file_content'] = '<html><body><p></p></body></html>';
                    }
                }
            }
        }
    }

    /**
     * Deletes the current certificate object. This is generally triggered by
     * the teacher from the gradebook tool to re-generate the certificate because
     * the original version wa flawed.
     *
     * @param bool $force_delete
     *
     * @return bool
     */
    public function deleteCertificate(): bool
    {
        if (empty($this->certificate_data)) {
            return false;
        }

        $categoryId = isset($this->certificate_data['cat_id']) ? (int) $this->certificate_data['cat_id'] : 0;
        $userId = isset($this->certificate_data['user_id']) ? (int) $this->certificate_data['user_id'] : 0;

        if ($userId <= 0) {
            return false;
        }

        $certRepo = Container::getGradeBookCertificateRepository();

        try {
            // Prefer the real method name (resource-first + legacy fallback)
            if (method_exists($certRepo, 'deleteCertificateAndRelatedFiles')) {
                return (bool) $certRepo->deleteCertificateAndRelatedFiles($userId, $categoryId);
            }

            // Backward compatible fallback
            if (method_exists($certRepo, 'deleteCertificateResource')) {
                return (bool) $certRepo->deleteCertificateResource($userId, $categoryId);
            }

            return false;
        } catch (\Throwable $e) {
            error_log('[CERTIFICATE::deleteCertificate] delete error: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Generates (or updates) the user's certificate as a Resource.
     *
     * Template strategy is centralized here:
     *  1) If the Gradebook category HAS a default template (document) => use it.
     *  2) If it DOES NOT => fall back to the portal "custom" template (legacy behavior).
     * In both cases we store a category-bound Resource (cat_id), so pages don't need to re-implement fallbacks.
     *
     * - Stores the HTML in a ResourceNode (resource_type = user_certificate).
     * - Fills $this->certificate_data['file_content'] with the HTML to avoid PDF errors.
     * - Keeps legacy DB info via registerUserInfoAboutCertificate() (no PersonalFile usage).
     *
     * @param array $params
     * @param bool  $sendNotification
     *
     * @return bool
     */
    public function generate($params = [], $sendNotification = false)
    {
        // Safe defaults
        $params = is_array($params) ? $params : [];
        $params['hide_print_button'] = isset($params['hide_print_button'])
            ? (bool) $params['hide_print_button']
            : false;

        // Repository (required).
        try {
            $certRepo = Container::getGradeBookCertificateRepository();
        } catch (\Throwable $e) {
            error_log('[CERT::generate] FATAL: cannot get GradeBookCertificateRepository: '.$e->getMessage());
            return false;
        }
        if (!$certRepo) {
            error_log('[CERT::generate] FATAL: GradeBookCertificateRepository is NULL');
            return false;
        }

        $categoryId = 0;
        $category   = null;
        $isCertificateAvailableInCategory = false;

        // If the certificate is linked to a Gradebook category, check availability
        if (isset($this->certificate_data['cat_id'])) {
            $categoryId = (int) $this->certificate_data['cat_id'];

            // Category::load() returns an array
            $myCategory = Category::load($categoryId);

            try {
                $repo = Container::getGradeBookCategoryRepository();
                /** @var GradebookCategory|null $category */
                $category = $repo ? $repo->find($categoryId) : null;
            } catch (\Throwable $e) {
                error_log('[CERT::generate] category repo fetch failed: '.$e->getMessage());
                $category = null;
            }

            if (!empty($categoryId) && !empty($myCategory) && isset($myCategory[0])) {
                $isCertificateAvailableInCategory = $myCategory[0]->is_certificate_available($this->user_id);
            }
        }

        // Path A: course/session-bound certificate (category context)
        if ($isCertificateAvailableInCategory && null !== $category) {
            // Course/session info
            $course     = $category->getCourse();
            $courseInfo = api_get_course_info($course->getCode());
            $courseId   = $courseInfo['real_id'];
            $sessionId  = $category->getSession() ? (int) $category->getSession()->getId() : 0;

            try {
                $skill = new SkillModel();
                $skill->addSkillToUser(
                    $this->user_id,
                    $category,
                    $courseId,
                    $sessionId
                );
            } catch (\Throwable $e) {
                error_log('[CERT::generate] addSkillToUser failed: '.$e->getMessage());
            }

            $categoryHasDefaultTemplate = false;
            $documentIdForLog = null;
            try {
                $doc = $category->getDocument();
                if ($doc !== null) {
                    $categoryHasDefaultTemplate = true;
                    try {
                        $documentIdForLog = method_exists($doc, 'getId') ? $doc->getId() : null;
                    } catch (\Throwable $ignored) {
                        $documentIdForLog = null;
                    }
                }
            } catch (\Throwable $e) {
                error_log('[CERT::generate] getDocument() failed (no default template): '.$e->getMessage());
                $categoryHasDefaultTemplate = false;
            }

            $gb = GradebookUtils::get_user_certificate_content(
                $this->user_id,
                $course->getId(),
                $sessionId,
                false,
                $params['hide_print_button']
            );

            $score = null;
            if (is_array($gb) && isset($gb['score']) && $gb['score'] !== '' && $gb['score'] !== null) {
                $score = (float) $gb['score'];
            }

            if (null === $score && isset($this->certificate_data['score_certificate']) && $this->certificate_data['score_certificate'] !== '') {
                $score = (float) $this->certificate_data['score_certificate'];
            }

            if (null === $score) {
                $score = 100.0;
            }

            $html   = '';
            $source = '';

            if ($categoryHasDefaultTemplate) {
                if (is_array($gb) && !empty($gb['content'])) {
                    $html   = (string)$gb['content'];
                    $source = 'DEFAULT_TEMPLATE';
                } elseif (is_string($gb) && $gb !== '') {
                    $html   = $gb;
                    $source = 'DEFAULT_TEMPLATE';
                }
            } else {
                error_log(sprintf(
                    '[CERT::generate] course DEFAULT template NOT found. cat=%d user=%d -> fallback to CUSTOM',
                    (int)$categoryId,
                    (int)$this->user_id
                ));
            }

            if ($html === '') {
                $html   = $this->generateCustomCertificate('');
                $source = 'CUSTOM_TEMPLATE_FALLBACK';
            }

            if ($html === '') {
                error_log(sprintf(
                    '[CERT::generate] Empty HTML on category path. cat=%d user=%d',
                    (int)$categoryId,
                    (int)$this->user_id
                ));
                return false;
            }

            try {
                // Persist as Resource and register legacy info (no PersonalFile)
                $entity = $certRepo->upsertCertificateResource($categoryId, $this->user_id, $score, $html);
                $certRepo->registerUserInfoAboutCertificate($categoryId, $this->user_id, $score);

                // Ensure PDF flow has the HTML in memory
                $this->certificate_data['file_content']     = $html;
                $this->certificate_data['path_certificate'] = '';

                // Send notification if required (we have course context here)
                if ($sendNotification) {
                    $subject = get_lang('Certificate notification');
                    $message = nl2br(get_lang('((user_first_name)),'));
                    $htmlUrl = '';
                    try {
                        $htmlUrl = $certRepo->getResourceFileUrl($entity);
                    } catch (\Throwable $e) {
                        error_log('[CERT::generate] getResourceFileUrl failed for notification: '.$e->getMessage());
                    }

                    self::sendNotification(
                        $subject,
                        $message,
                        api_get_user_info($this->user_id),
                        $courseInfo,
                        [
                            'score_certificate' => $score,
                            'html_url'          => $htmlUrl,
                        ]
                    );
                }

                return true;
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[CERT::generate] Upsert FAILED (course). cat=%d user=%d err=%s',
                    (int)$categoryId,
                    (int)$this->user_id,
                    $e->getMessage()
                ));
                return false;
            }
        }

        // Path B: general (portal-wide) certificate
        try {
            $html  = $this->generateCustomCertificate('');
            $score = 100.0;

            if ($html === '') {
                error_log(sprintf(
                    '[CERT::generate] Empty HTML on general path. user=%d',
                    (int)$this->user_id
                ));
                return false;
            }

            $entity = $certRepo->upsertCertificateResource(0, $this->user_id, $score, $html);
            $certRepo->registerUserInfoAboutCertificate(0, $this->user_id, $score);

            $this->certificate_data['file_content']     = $html;
            $this->certificate_data['path_certificate'] = ''; // resource

            return true;
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[CERT::generate] Upsert FAILED (general). user=%d err=%s',
                (int)$this->user_id,
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * @return array
     */
    public static function notificationTags()
    {
        $tags = [
            '((course_title))',
            '((user_first_name))',
            '((user_last_name))',
            '((author_first_name))',
            '((author_last_name))',
            '((score))',
            '((portal_name))',
            '((certificate_link))',
        ];

        return $tags;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array  $userInfo
     * @param array  $courseInfo
     * @param array  $certificateInfo
     *
     * @return bool
     */
    public static function sendNotification(
        $subject,
        $message,
        $userInfo,
        $courseInfo,
        $certificateInfo
    ) {
        if (empty($userInfo) || empty($courseInfo)) {
            return false;
        }

        $currentUserInfo = api_get_user_info();
        $url = '';

        // Prefer resource URL if present
        if (!empty($certificateInfo['html_url'])) {
            $url = $certificateInfo['html_url'];
        } elseif (!empty($certificateInfo['path_certificate'])) {
            $hash = pathinfo($certificateInfo['path_certificate'], PATHINFO_FILENAME);
            $url = api_get_path(WEB_PATH) . 'certificates/' . $hash . '.html';
        }
        $link = Display::url($url, $url);

        $replace = [
            $courseInfo['title'],
            $userInfo['firstname'],
            $userInfo['lastname'],
            $currentUserInfo['firstname'],
            $currentUserInfo['lastname'],
            $certificateInfo['score_certificate'],
            api_get_setting('Institution'),
            $link,
        ];

        $message = str_replace(self::notificationTags(), $replace, $message);
        MessageManager::send_message(
            $userInfo['id'],
            $subject,
            $message,
            [],
            [],
            0,
            0,
            0,
            0,
            $currentUserInfo['id']
        );
    }

    /**
     * Update user info about certificate.
     *
     * @param int    $categoryId            category id
     * @param int    $user_id               user id
     * @param string $path_certificate      the path name of the certificate
     * @param bool   $updateCertificateData
     */
    public function updateUserCertificateInfo(
        $categoryId,
        $user_id,
        $path_certificate,
        $updateCertificateData = true
    ) {
        if (!$updateCertificateData) {
            return;
        }
        $certRepo = Container::getGradeBookCertificateRepository();

        $certRepo->registerUserInfoAboutCertificate(
            (int)$categoryId,
            (int)$user_id,
            (float)($this->certificate_data['score_certificate'] ?? 100.0),
            (string)$path_certificate
        );
    }

    /**
     * Check if the file was generated.
     *
     * @return bool
     */
    public function isHtmlFileGenerated()
    {
        if (empty($this->certification_user_path)) {
            return false;
        }
        if (!empty($this->certificate_data) &&
            isset($this->certificate_data['path_certificate']) &&
            !empty($this->certificate_data['path_certificate'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generates a QR code for the certificate. The QR code embeds the text given.
     *
     * @param string $text Text to be added in the QR code
     * @param string $path file path of the image
     *
     * @return bool
     */
    public function generateQRImage($text, $path): bool
    {
        throw new \Exception('generateQRImage');
        if (!empty($text) && !empty($path)) {
            $qrCode = new QrCode($text);
            //$qrCode->setEncoding('UTF-8');
            $qrCode->setSize(120);
            $qrCode->setMargin(5);
            /*$qrCode->setWriterByName('png');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
            $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
            $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
            $qrCode->setValidateResult(false);
            $qrCode->writeFile($path);*/

            return true;
        }

        return false;
    }

    /**
     * Transforms certificate tags into text values. This function is very static
     * (it doesn't allow for much flexibility in terms of what tags are printed).
     *
     * @param array $array Contains two array entries: first are the headers,
     *                     second is an array of contents
     *
     * @return string The translated string
     */
    public function parseCertificateVariables($array)
    {
        $headers = $array[0];
        $content = $array[1];
        $final_content = [];

        if (!empty($content)) {
            foreach ($content as $key => $value) {
                $my_header = str_replace(['((', '))'], '', $headers[$key]);
                $final_content[$my_header] = $value;
            }
        }

        /* Certificate tags
         *
          0 => string '((user_firstname))' (length=18)
          1 => string '((user_lastname))' (length=17)
          2 => string '((gradebook_institution))' (length=25)
          3 => string '((gradebook_sitename))' (length=22)
          4 => string '((teacher_firstname))' (length=21)
          5 => string '((teacher_lastname))' (length=20)
          6 => string '((official_code))' (length=17)
          7 => string '((date_certificate))' (length=20)
          8 => string '((course_code))' (length=15)
          9 => string '((course_title))' (length=16)
          10 => string '((gradebook_grade))' (length=19)
          11 => string '((certificate_link))' (length=20)
          12 => string '((certificate_link_html))' (length=25)
          13 => string '((certificate_barcode))' (length=23)
         */

        $break_space = " \n\r ";
        $text =
            $final_content['gradebook_institution'].' - '.
            $final_content['gradebook_sitename'].' - '.
            get_lang('Certification').$break_space.
            get_lang('Learner').': '.$final_content['user_firstname'].' '.$final_content['user_lastname'].$break_space.
            get_lang('Trainer').': '.$final_content['teacher_firstname'].' '.$final_content['teacher_lastname'].$break_space.
            get_lang('Date').': '.$final_content['date_certificate'].$break_space.
            get_lang('Score').': '.$final_content['gradebook_grade'].$break_space.
            'URL'.': '.$final_content['certificate_link'];

        return $text;
    }

    /**
     * Check if the certificate is visible for the current user
     * If the global setting allow_public_certificates is set to 'false', no certificate can be printed.
     * If the global allow_public_certificates is set to 'true' and the course setting allow_public_certificates
     * is set to 0, no certificate *in this course* can be printed (for anonymous users).
     * Connected users can always print them.
     *
     * @return bool
     */
    public function isVisible()
    {
        if (!api_is_anonymous()) {
            return true;
        }

        if ('true' != api_get_setting('certificate.allow_public_certificates')) {
            // The "non-public" setting is set, so do not print
            return false;
        }

        if (!isset($this->certificate_data, $this->certificate_data['cat_id'])) {
            return false;
        }

        $gradeBook = new Gradebook();
        $gradeBookInfo = $gradeBook->get($this->certificate_data['cat_id']);

        if (empty($gradeBookInfo['course_code'])) {
            return false;
        }

        $setting = api_get_course_setting(
            'allow_public_certificates',
            api_get_course_info($gradeBookInfo['course_code'])
        );

        if (0 == $setting) {
            // Printing not allowed
            return false;
        }

        return true;
    }

    /**
     * Check if the certificate is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        $certRepo = Container::getGradeBookCertificateRepository();

        $categoryId = isset($this->certificate_data['cat_id']) ? (int) $this->certificate_data['cat_id'] : 0;

        try {
            $entity = $certRepo->getCertificateByUserId(0 === $categoryId ? null : $categoryId, $this->user_id);
            if (!$entity || !$entity->hasResourceNode()) {
                return false;
            }

            $node = $entity->getResourceNode();
            return $node->hasResourceFile() && $node->getResourceFiles()->count() > 0;
        } catch (\Throwable $e) {
            error_log('[CERTIFICATE::isAvailable] check error: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Shows the student's certificate (HTML file).
     */
    public function show()
    {
        $certRepo = Container::getGradeBookCertificateRepository();
        $categoryId = isset($this->certificate_data['cat_id']) ? (int) $this->certificate_data['cat_id'] : 0;

        try {
            $entity = $certRepo->getCertificateByUserId(0 === $categoryId ? null : $categoryId, $this->user_id);
            if (!$entity || !$entity->hasResourceNode()) {
                api_not_allowed(true);
            }

            // Read HTML content from the Resource layer
            $certificateContent = '<!DOCTYPE html>';
            $certificateContent .= $certRepo->getResourceFileContent($entity);
            $certificateContent = str_replace(' media="screen"', '', $certificateContent);

            // Track “downloaded_at” (legacy extra fields)
            if ($this->user_id == api_get_user_id() &&
                !empty($this->certificate_data) &&
                isset($this->certificate_data['id'])
            ) {
                $certificateId = $this->certificate_data['id'];
                $extraFieldValue = new ExtraFieldValue('user_certificate');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $certificateId,
                    'downloaded_at'
                );
                if (empty($value)) {
                    $params = [
                        'item_id' => $this->certificate_data['id'],
                        'extra_downloaded_at' => api_get_utc_datetime(),
                    ];
                    $extraFieldValue->saveFieldValues($params);
                }
            }

            header('Content-Type: text/html; charset='.api_get_system_encoding());
            echo $certificateContent;
            return;
        } catch (\Throwable $e) {
            error_log('[CERTIFICATE::show] read error: '.$e->getMessage());
            api_not_allowed(true);
        }
    }

    /**
     * @return string
     */
    public function generateCustomCertificate(string $fileName = ''): string
    {
        $certificateRepo = Container::getGradeBookCertificateRepository();
        $certificateRepo->registerUserInfoAboutCertificate(0, $this->user_id, 100, $fileName);

        $userInfo = api_get_user_info($this->user_id);
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($this->user_id, 'legal_accept');
        $termsValidationDate = '';
        if (isset($value) && !empty($value['value'])) {
            [$id, $id2, $termsValidationDate] = explode(':', $value['value']);
        }

        $sessions = SessionManager::get_sessions_by_user($this->user_id, false, true);
        $totalTimeInLearningPaths = 0;
        $sessionsApproved = [];
        $coursesApproved = [];
        $courseList = [];

        $gradeBookRepo = Container::getGradeBookCategoryRepository();
        if ($sessions) {
            foreach ($sessions as $session) {
                $allCoursesApproved = [];
                foreach ($session['courses'] as $course) {
                    $course = api_get_course_entity($course['real_id']);
                    $courseId = $course->getId();
                    /* @var GradebookCategory $category */
                    $category = $gradeBookRepo->findOneBy(['course' => $course, 'session' => $session['session_id']]);

                    if (null !== $category) {
                        $result = Category::userFinishedCourse(
                            $this->user_id,
                            $category,
                            true,
                            $courseId,
                            $session['session_id']
                        );

                        $lpList = new LearnpathList(
                            $this->user_id,
                            api_get_course_info_by_id($courseId),
                            $session['session_id']
                        );
                        $lpFlatList = $lpList->get_flat_list();

                        // Find time spent in LP
                        $timeSpent = Tracking::get_time_spent_in_lp(
                            $this->user_id,
                            $course,
                            !empty($lpFlatList) ? array_keys($lpFlatList) : [],
                            $session['session_id']
                        );

                        if (!isset($courseList[$courseId])) {
                            $courseList[$courseId]['approved'] = false;
                            $courseList[$courseId]['time_spent'] = 0;
                        }

                        if ($result) {
                            $courseList[$courseId]['approved'] = true;
                            $coursesApproved[$courseId] = $course->getTitle();
                            $allCoursesApproved[] = true;
                        }
                        $courseList[$courseId]['time_spent'] += $timeSpent;
                    }
                }

                if (count($allCoursesApproved) == count($session['courses'])) {
                    $sessionsApproved[] = $session;
                }
            }
        }

        $totalTimeInLearningPaths = 0;
        foreach ($courseList as $courseId => $courseData) {
            if (true === $courseData['approved']) {
                $totalTimeInLearningPaths += $courseData['time_spent'];
            }
        }

        $skill = new SkillModel();
        $skills = $skill->getStudentSkills($this->user_id, 2);
        $allowAll = ('true' === api_get_setting('skill.allow_teacher_access_student_skills'));
        $courseIdForSkills  = $allowAll ? 0 : 0;
        $sessionIdForSkills = $allowAll ? 0 : 0;
        $skillsTable = $skill->getUserSkillsTable(
            $this->user_id,
            $courseIdForSkills,
            $sessionIdForSkills,
            false
        );

        $timeInSeconds = Tracking::get_time_spent_on_the_platform(
            $this->user_id,
            'ever'
        );
        $time = api_time_to_hms($timeInSeconds);

        $tplContent = new Template(null, false, false, false, false, false);

        // variables for the default template
        $tplContent->assign('complete_name', $userInfo['complete_name']);
        $tplContent->assign('time_in_platform', $time);
        $tplContent->assign('certificate_generated_date', isset($myCertificate['created_at']) ? api_get_local_time($myCertificate['created_at']) : '');
        if (!empty($termsValidationDate)) {
            $termsValidationDate = api_get_local_time($termsValidationDate);
        }
        $tplContent->assign('terms_validation_date', $termsValidationDate);

        if (empty($totalTimeInLearningPaths)) {
            $totalTimeInLearningPaths = $timeInSeconds;
        }

        // Ofaj
        $tplContent->assign('time_in_platform_in_hours', round($timeInSeconds/3600, 1));
        $tplContent->assign(
            'certificate_generated_date_no_time',
            api_get_local_time(
                $myCertificate['created_at'] ?? null,
                null,
                null,
                false,
                false,
                false,
                'd-m-Y'
            )
        );
        $tplContent->assign(
            'terms_validation_date_no_time',
            api_get_local_time(
                $termsValidationDate,
                null,
                null,
                false,
                false,
                false,
                'd-m-Y'
            )
        );
        $tplContent->assign('skills', $skills);
        $tplContent->assign('skills_table_html', $skillsTable['table']);
        $tplContent->assign('skills_rows', $skillsTable['skills']);
        $tplContent->assign('sessions', $sessionsApproved);
        $tplContent->assign('courses', $coursesApproved);
        $tplContent->assign('time_spent_in_lps', api_time_to_hms($totalTimeInLearningPaths));
        $tplContent->assign('time_spent_in_lps_in_hours', round($totalTimeInLearningPaths/3600, 1));

        $layoutContent = $tplContent->get_template('gradebook/custom_certificate.html.twig');
        $content = $tplContent->fetch($layoutContent);

        return $content;
    }

    /**
     * Ofaj.
     */
    public function generatePdfFromCustomCertificate(): void
    {
        $orientation = api_get_setting('certificate.certificate_pdf_orientation');

        $params['orientation'] = 'landscape';
        if (!empty($orientation)) {
            $params['orientation'] = $orientation;
        }

        $params['left'] = 0;
        $params['right'] = 0;
        $params['top'] = 0;
        $params['bottom'] = 0;
        $page_format = 'landscape' == $params['orientation'] ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $params['orientation'], $params);

        // Safety: ensure HTML content is present; fetch from Resource if needed.
        if (empty($this->certificate_data['file_content'])) {
            try {
                $certRepo   = Container::getGradeBookCertificateRepository();
                $categoryId = isset($this->certificate_data['cat_id']) ? (int) $this->certificate_data['cat_id'] : 0;
                $entity     = $certRepo->getCertificateByUserId(0 === $categoryId ? null : $categoryId, $this->user_id);
                if ($entity && $entity->hasResourceNode()) {
                    $this->certificate_data['file_content'] = $certRepo->getResourceFileContent($entity);
                }
            } catch (\Throwable $e) {
                error_log('[CERT::generatePdfFromCustomCertificate] fallback read error: '.$e->getMessage());
            }
        }

        $pdf->content_to_pdf(
            $this->certificate_data['file_content'],
            null,
            get_lang('Certificates'),
            api_get_course_id(),
            'D',
            false,
            null,
            false,
            true,
            true,
            true,
            true
        );
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getCertificateByUser($userId)
    {
        $userId = (int) $userId;
        if (empty($userId)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = "SELECT * FROM $table
                WHERE user_id= $userId";
        $rs = Database::query($sql);

        return Database::store_result($rs, 'ASSOC');
    }

    /**
     * @param int $userId
     */
    public static function generateUserSkills($userId)
    {
        $controller = new IndexManager(get_lang('My courses'));
        $courseAndSessions = $controller->returnCoursesAndSessions($userId, true, null, true, false);
        $repo = Container::getGradeBookCategoryRepository();
        if (isset($courseAndSessions['courses']) && !empty($courseAndSessions['courses'])) {
            foreach ($courseAndSessions['courses'] as $course) {
                $category = $repo->findOneBy(['course' => $course['real_id']]);
                /*$cats = Category::load(
                    null,
                    null,
                    $course['code'],
                    null,
                    null,
                    null,
                    false
                );*/
                if (null !== $category) {
                    Category::generateUserCertificate($category, $userId);
                }
            }
        }

        if (isset($courseAndSessions['sessions']) && !empty($courseAndSessions['sessions'])) {
            foreach ($courseAndSessions['sessions'] as $sessionCategory) {
                if (isset($sessionCategory['sessions'])) {
                    foreach ($sessionCategory['sessions'] as $sessionData) {
                        if (!empty($sessionData['courses'])) {
                            $sessionId = $sessionData['session_id'];
                            foreach ($sessionData['courses'] as $courseData) {
                                /*$cats = Category:: load(
                                    null,
                                    null,
                                    $courseData['course_code'],
                                    null,
                                    null,
                                    $sessionId,
                                    false
                                );*/

                                $category = $repo->findOneBy(
                                    ['course' => $courseData['real_id'], 'session' => $sessionId]
                                );
                                if (null !== $category) {
                                    Category::generateUserCertificate($category, $userId);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
