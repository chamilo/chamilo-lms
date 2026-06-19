<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

/**
 * Class GradingElectronicPlugin.
 */
class GradingElectronicPlugin extends Plugin
{
    public const EXTRAFIELD_STUDENT_ID = 'fcdice_or_acadis_student_id';
    public const EXTRAFIELD_COURSE_PROVIDER_ID = 'plugin_gradingelectronic_provider_id';
    public const EXTRAFIELD_COURSE_ID = 'plugin_gradingelectronic_course_id';
    public const EXTRAFIELD_COURSE_HOURS = 'plugin_gradingelectronic_coursehours';

    protected function __construct()
    {
        parent::__construct(
            '0.8',
            'Angel Fernando Quiroz Campos, Julio Montoya',
            []
        );
    }

    /**
     * @return GradingElectronicPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Actions for install.
     */
    public function install()
    {
        $this->setUpExtraFields();
    }

    /**
     * Actions for uninstall.
     */
    public function uninstall()
    {
        $this->setDownExtraFields();
    }

    /**
     * Keep the legacy API available for old templates.
     *
     * @return FormValidator|null
     */
    public function getForm()
    {
        $extraField = new ExtraField('course');
        $courseIdField = $extraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        );

        if (!$courseIdField) {
            return null;
        }

        $courseIdValue = $this->getExtraFieldStoredValue(
            'course',
            api_get_course_int_id(),
            self::EXTRAFIELD_COURSE_ID
        );

        $form = new FormValidator('frm_grading_electronic');
        $form->addDateRangePicker(
            'range',
            get_lang('Date range'),
            true,
            [
                'id' => 'range',
                'format' => 'YYYY-MM-DD',
                'timePicker' => false,
                'validate_format' => 'Y-m-d',
            ]
        );
        $form->addText('course', $this->getPluginText('CourseId', 'Course ID'));
        $form->addButtonDownload(get_lang('Generate'));
        $form->addRule('course', get_lang('Required field'), 'required');
        $form->setDefaults([
            'course' => $courseIdValue,
        ]);

        return $form;
    }

    /**
     * Check if the current user is allowed to see the button.
     *
     * @return bool
     */
    public function isAllowed()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (api_is_platform_admin()) {
            return true;
        }

        if (function_exists('api_is_allowed_to_edit') && api_is_allowed_to_edit(null, true)) {
            return true;
        }

        return api_is_teacher() || api_is_course_tutor();
    }

    /**
     * Render the plugin button in assigned regions.
     *
     * @param string $region
     *
     * @return string
     */
    public function renderRegion($region)
    {
        if (!in_array($region, ['content_top', 'content_bottom'], true)) {
            return '';
        }

        if (!$this->isGradebookPage()) {
            return '';
        }

        if ($this->isGenerateRequest()) {
            return $this->generateFromRequest();
        }

        return $this->renderGradebookExport();
    }

    /**
     * Render the gradebook export form.
     *
     * @return string
     */
    public function renderGradebookExport(bool $force = false)
    {
        if (!$this->isEnabled()) {
            return '';
        }

        if (!$force && !$this->isAllowed()) {
            return '';
        }

        return $this->renderFormHtml(
            $this->getGenerateUrl(),
            'grading-electronic-region-result'
        );
    }

    public function getGenerateUrl(string $region = ''): string
    {
        $params = [];
        $cidReq = api_get_cidreq();

        if (!empty($cidReq)) {
            parse_str(str_replace('&amp;', '&', $cidReq), $params);
        }

        foreach (['cid', 'cidReq', 'sid', 'id_session', 'gid', 'origin', 'gradebook'] as $key) {
            if (isset($_GET[$key]) && '' !== (string) $_GET[$key]) {
                $params[$key] = $_GET[$key];
            }
        }

        foreach (['grading_electronic_action', 'range_start', 'range_end', 'course', 'file'] as $key) {
            unset($params[$key]);
        }

        $query = http_build_query($params);

        return api_get_path(WEB_PATH).'main/gradebook/index.php'.('' !== $query ? '?'.$query : '');
    }

    public function renderFormHtml(string $generateUrl, string $resultId = 'grading-electronic-result'): string
    {
        $courseIdValue = '';

        try {
            $courseIdValue = (string) $this->getExtraFieldStoredValue(
                'course',
                api_get_course_int_id(),
                self::EXTRAFIELD_COURSE_ID
            );
        } catch (Throwable $exception) {
            $courseIdValue = '';
        }

        $today = date('Y-m-d');
        $formId = $resultId.'-form';
        $errorMessage = $this->getAlertHtml(
            get_lang('An error occurred'),
            'error'
        );
        $loadingMessage = $this->getAlertHtml(
            get_lang('Loading'),
            'info'
        );

        ob_start();
        ?>
        <section class="grading-electronic-wrapper mb-6">
            <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
                <div class="flex flex-col gap-3 border-b border-gray-25 bg-support-2 px-6 py-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="mdi mdi-file-document-outline ch-tool-icon text-2xl text-primary" aria-hidden="true"></span>
                        <div>
                            <h3 class="m-0 text-xl font-semibold text-gray-90">
                                <?php echo Security::remove_XSS($this->getPluginText('plugin_title', 'Grading Electronic')); ?>
                            </h3>
                            <p class="m-0 mt-1 text-body-2 text-gray-50">
                                <?php echo Security::remove_XSS($this->getPluginText('GenerateFile', 'Generate file')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    name="frm_grading_electronic"
                    id="<?php echo Security::remove_XSS($formId); ?>"
                    method="post"
                    class="space-y-4 p-6"
                >
                    <input type="hidden" name="grading_electronic_action" value="generate">

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-1">
                            <label for="<?php echo Security::remove_XSS($formId); ?>-range-start" class="block text-body-2 font-semibold text-gray-90">
                                <?php echo Security::remove_XSS(get_lang('Start date')); ?>
                            </label>
                            <input
                                type="date"
                                class="block w-full rounded-lg border border-gray-25 bg-white px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                                id="<?php echo Security::remove_XSS($formId); ?>-range-start"
                                name="range_start"
                                value="<?php echo Security::remove_XSS($today); ?>"
                                required
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="<?php echo Security::remove_XSS($formId); ?>-range-end" class="block text-body-2 font-semibold text-gray-90">
                                <?php echo Security::remove_XSS(get_lang('End date')); ?>
                            </label>
                            <input
                                type="date"
                                class="block w-full rounded-lg border border-gray-25 bg-white px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                                id="<?php echo Security::remove_XSS($formId); ?>-range-end"
                                name="range_end"
                                value="<?php echo Security::remove_XSS($today); ?>"
                                required
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="<?php echo Security::remove_XSS($formId); ?>-course" class="block text-body-2 font-semibold text-gray-90">
                                <?php echo Security::remove_XSS($this->getPluginText('CourseId', 'Course ID')); ?>
                            </label>
                            <input
                                type="text"
                                class="block w-full rounded-lg border border-gray-25 bg-white px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                                id="<?php echo Security::remove_XSS($formId); ?>-course"
                                name="course"
                                value="<?php echo Security::remove_XSS((string) $courseIdValue); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-25 pt-4 md:flex-row md:items-center md:justify-between">
                        <div id="<?php echo Security::remove_XSS($resultId); ?>" class="w-full text-body-2 md:flex-1"></div>

                        <button type="submit" class="btn btn--primary inline-flex items-center justify-center gap-2">
                            <span class="mdi mdi-file-download-outline" aria-hidden="true"></span>
                            <?php echo Security::remove_XSS(get_lang('Generate')); ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <script>
            $(function () {
                var generateUrl = <?php echo json_encode($generateUrl); ?>;
                var errorMessage = <?php echo json_encode($errorMessage); ?>;
                var loadingMessage = <?php echo json_encode($loadingMessage); ?>;
                var formSelector = '#<?php echo addslashes($formId); ?>';
                var resultSelector = '#<?php echo addslashes($resultId); ?>';

                $(formSelector).off('submit.gradingElectronic').on('submit.gradingElectronic', function (e) {
                    e.preventDefault();

                    var $self = $(this);
                    var $button = $self.find('button[type="submit"]');
                    var $result = $(resultSelector);

                    $button.prop('disabled', true);
                    $result.html(loadingMessage);

                    $.ajax({
                        url: generateUrl,
                        type: 'POST',
                        data: $self.serialize(),
                        dataType: 'html'
                    }).done(function (response) {
                        var html = '';

                        try {
                            var parsedResponse = $.parseHTML(response, document, true);
                            var $response = $('<div>').append(parsedResponse);
                            html = $response.find('[data-grading-electronic-generation-result]').first().html() || '';
                        } catch (error) {
                            html = '';
                        }

                        $result.html(html || errorMessage);
                    }).fail(function () {
                        $result.html(errorMessage);
                    }).always(function () {
                        $button.prop('disabled', false);
                    });
                });
            });
        </script>
        <?php

        return (string) ob_get_clean();
    }

    public function isGenerateRequest(): bool
    {
        return 'generate' === (string) ($_REQUEST['grading_electronic_action'] ?? '');
    }

    public function generateFromRequest(): string
    {
        try {
            if (!$this->isAllowed()) {
                throw new Exception(get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'));
            }

            $rangeStart = trim((string) ($_REQUEST['range_start'] ?? ''));
            $rangeEnd = trim((string) ($_REQUEST['range_end'] ?? ''));
            $externalCourseId = trim((string) ($_REQUEST['course'] ?? ''));

            if ('' === $rangeStart || '' === $rangeEnd || '' === $externalCourseId) {
                throw new Exception(get_lang('Required field'));
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeEnd)) {
                throw new Exception(get_lang('Invalid date'));
            }

            $dateStart = new DateTime($rangeStart.' 00:00:00', new DateTimeZone('UTC'));
            $dateEnd = new DateTime($rangeEnd.' 23:59:59', new DateTimeZone('UTC'));

            if ($dateStart > $dateEnd) {
                throw new Exception(get_lang('Invalid date'));
            }

            $em = Database::getManager();
            $courseId = api_get_course_int_id();

            if (0 >= $courseId) {
                throw new Exception(get_lang('Missing course information'));
            }

            /** @var Course|null $course */
            $course = $em->find(Course::class, $courseId);

            if (!$course) {
                throw new Exception(get_lang('Course not found'));
            }

            /** @var Session|null $session */
            $session = null;
            $sessionId = api_get_session_id();

            if (!empty($sessionId)) {
                $session = $em->find(Session::class, $sessionId);
            }

            $this->saveExtraFieldStoredValue(
                'course',
                $course->getId(),
                self::EXTRAFIELD_COURSE_ID,
                $externalCourseId
            );

            $providerId = $this->getExtraFieldStoredValue(
                'course',
                $course->getId(),
                self::EXTRAFIELD_COURSE_PROVIDER_ID
            );
            $courseHours = $this->getExtraFieldStoredValue(
                'course',
                $course->getId(),
                self::EXTRAFIELD_COURSE_HOURS
            );

            $students = [];

            if ($session) {
                $criteria = Criteria::create()->where(
                    Criteria::expr()->eq('relationType', Session::STUDENT)
                );

                $subscriptions = $session->getUsers()->matching($criteria);

                /** @var SessionRelUser $subscription */
                foreach ($subscriptions as $subscription) {
                    $students[] = $subscription->getUser();
                }
            } else {
                $subscriptions = $course->getStudentSubscriptions();

                /** @var CourseRelUser $subscription */
                foreach ($subscriptions as $subscription) {
                    $students[] = $subscription->getUser();
                }
            }

            $cats = Category::load(
                null,
                null,
                $course->getId(),
                null,
                null,
                $session ? $session->getId() : 0,
                'ORDER BY id'
            );

            if (empty($cats[0])) {
                throw new Exception(get_lang('No gradebook found'));
            }

            /** @var Category $gradebook */
            $gradebook = $cats[0];

            /** @var GradebookCategory|null $gradebookCategory */
            $gradebookCategory = $em->find(GradebookCategory::class, $gradebook->get_id());

            if (!$gradebookCategory) {
                throw new Exception(get_lang('No gradebook found'));
            }

            $fileData = [];
            $fileData[] = sprintf(
                '1 %s %s%s',
                (string) $providerId,
                $externalCourseId,
                $dateStart->format('m/d/Y')
            );

            /** @var User $student */
            foreach ($students as $student) {
                $userFinishedCourse = Category::userFinishedCourse(
                    $student->getId(),
                    $gradebookCategory,
                    true,
                    $course->getId(),
                    $session ? $session->getId() : 0
                );

                if (!$userFinishedCourse) {
                    continue;
                }

                $studentId = $this->getExtraFieldStoredValue(
                    'user',
                    $student->getId(),
                    self::EXTRAFIELD_STUDENT_ID
                );
                $scoretotal = $gradebook->calc_score($student->getId());
                $scoredisplay = ScoreDisplay::instance();
                $score = $scoredisplay->display_score(
                    $scoretotal,
                    SCORE_SIMPLE
                );

                $fileData[] = sprintf(
                    '2 %sPASS%s %s %s',
                    (string) $studentId,
                    (string) $courseHours,
                    $score,
                    $dateEnd->format('m/d/Y')
                );

                if (!$gradebook->getGenerateCertificates()) {
                    continue;
                }

                Category::generateUserCertificate($gradebookCategory, $student->getId(), true);
            }

            $fileName = implode('_', [
                'GradingElectronic',
                $externalCourseId,
                $rangeStart,
                $rangeEnd,
            ]);
            $fileName = api_replace_dangerous_char($fileName).'.txt';
            $fileData[] = null;

            $filePath = api_get_path(SYS_ARCHIVE_PATH).$fileName;
            $saved = file_put_contents(
                $filePath,
                implode("\r\n", $fileData)
            );

            if (false === $saved) {
                throw new Exception(get_lang('The file could not be created'));
            }

            return $this->getDownloadButtonHtml($fileName);
        } catch (Throwable $exception) {
            error_log('[GradingElectronic] '.$exception->getMessage());

            return $this->getAlertHtml($exception->getMessage(), 'error');
        }
    }

    public function isDownloadRequest(): bool
    {
        return 'download' === (string) ($_REQUEST['grading_electronic_action'] ?? '');
    }

    public function downloadFromRequest(): void
    {
        try {
            if (!$this->isAllowed()) {
                throw new Exception(get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'));
            }

            $fileName = trim((string) ($_REQUEST['file'] ?? ''));
            $filePath = $this->getArchiveFilePath($fileName);

            if (null === $filePath) {
                throw new Exception(get_lang('File not found'));
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'.$this->sanitizeHeaderFileName(basename($fileName)).'"');
            header('Content-Length: '.(string) filesize($filePath));
            header('X-Content-Type-Options: nosniff');

            readfile($filePath);
            exit;
        } catch (Throwable $exception) {
            error_log('[GradingElectronic] '.$exception->getMessage());

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $exception->getMessage();
            exit;
        }
    }

    private function getDownloadButtonHtml(string $fileName): string
    {
        $downloadUrl = $this->getDownloadUrl($fileName);

        return sprintf(
            '<a class="btn btn--success inline-flex items-center justify-center gap-2" href="%s" target="_blank" download="%s"><span class="mdi mdi-download" aria-hidden="true"></span>%s</a>',
            Security::remove_XSS($downloadUrl),
            Security::remove_XSS($fileName),
            Security::remove_XSS(get_lang('Download'))
        );
    }

    private function getDownloadUrl(string $fileName): string
    {
        $params = [];
        $cidReq = api_get_cidreq();

        if (!empty($cidReq)) {
            parse_str(str_replace('&amp;', '&', $cidReq), $params);
        }

        foreach (['cid', 'cidReq', 'sid', 'id_session', 'gid', 'origin', 'gradebook'] as $key) {
            if (isset($_GET[$key]) && '' !== (string) $_GET[$key]) {
                $params[$key] = $_GET[$key];
            }
        }

        foreach (['range_start', 'range_end', 'course'] as $key) {
            unset($params[$key]);
        }

        $params['grading_electronic_action'] = 'download';
        $params['file'] = $fileName;

        return api_get_path(WEB_PATH).'main/gradebook/index.php?'.http_build_query($params);
    }

    private function getArchiveFilePath(string $fileName): ?string
    {
        $baseName = basename($fileName);

        if ($baseName !== $fileName) {
            return null;
        }

        if (!preg_match('/\AGradingElectronic[-_][A-Za-z0-9_.-]+\.txt\z/', $baseName)) {
            return null;
        }

        $archivePath = realpath(api_get_path(SYS_ARCHIVE_PATH));

        if (false === $archivePath) {
            return null;
        }

        $filePath = realpath($archivePath.DIRECTORY_SEPARATOR.$baseName);

        if (false === $filePath || !is_file($filePath)) {
            return null;
        }

        $normalizedArchivePath = rtrim($archivePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (0 !== strpos($filePath, $normalizedArchivePath)) {
            return null;
        }

        return $filePath;
    }

    private function sanitizeHeaderFileName(string $fileName): string
    {
        return str_replace(['"', "\r", "\n"], '', $fileName);
    }

    public function getExtraFieldStoredValue(string $itemType, int $itemId, string $variable): ?string
    {
        if (0 >= $itemId) {
            return null;
        }

        $extraFieldValue = new ExtraFieldValue($itemType);
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $itemId,
            $variable
        );

        return $this->extractExtraFieldValue($value);
    }

    public function saveExtraFieldStoredValue(string $itemType, int $itemId, string $variable, ?string $value): bool
    {
        if (0 >= $itemId) {
            return false;
        }

        $value = null === $value ? '' : $value;
        $extraFieldValue = new ExtraFieldValue($itemType);

        try {
            $extraFieldValue->save([
                'variable' => $variable,
                'item_id' => $itemId,
                'value' => $value,
                'field_value' => $value,
                'extra_'.$variable => $value,
            ]);
        } catch (Throwable $exception) {
            // Fallback below handles Chamilo 2 installations where ExtraFieldValue still expects legacy keys.
        }

        if ($value === (string) $this->getExtraFieldStoredValue($itemType, $itemId, $variable)) {
            return true;
        }

        return $this->upsertExtraFieldValue($itemType, $itemId, $variable, $value);
    }

    /**
     * Create extra fields for this plugin.
     */
    private function setUpExtraFields()
    {
        $uExtraField = new ExtraField('user');

        if (!$uExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_STUDENT_ID
        )) {
            $uExtraField->save([
                'variable' => self::EXTRAFIELD_STUDENT_ID,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->getPluginText('StudentId', 'Student ID'),
                'visible_to_self' => true,
                'changeable' => true,
                'filter' => 0,
            ]);
        }

        $cExtraField = new ExtraField('course');

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_PROVIDER_ID
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_PROVIDER_ID,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->getPluginText('ProviderId', 'Provider ID'),
                'visible_to_self' => true,
                'changeable' => true,
                'filter' => 0,
            ]);
        }

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_ID,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->getPluginText('CourseId', 'Course ID'),
                'visible_to_self' => true,
                'changeable' => true,
                'filter' => 0,
            ]);
        }

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_HOURS
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_HOURS,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->getPluginText('CourseHours', 'Course hours'),
                'visible_to_self' => true,
                'changeable' => true,
                'filter' => 0,
            ]);
        }
    }

    /**
     * Remove extra fields for this plugin.
     */
    private function setDownExtraFields()
    {
        $uExtraField = new ExtraField('user');
        $studentIdField = $uExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_STUDENT_ID
        );

        if ($studentIdField) {
            $uExtraField->delete($studentIdField['id']);
        }

        $cExtraField = new ExtraField('course');
        $providerIdField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_PROVIDER_ID
        );
        $courseIdField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        );
        $courseHoursField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_HOURS
        );

        if ($providerIdField) {
            $cExtraField->delete($providerIdField['id']);
        }

        if ($courseIdField) {
            $cExtraField->delete($courseIdField['id']);
        }

        if ($courseHoursField) {
            $cExtraField->delete($courseHoursField['id']);
        }
    }


    private function getCurrentPluginRegion(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        if (preg_match('#/plugin-regions/([a-z_]+)#', $requestUri, $matches)) {
            return (string) $matches[1];
        }

        return '';
    }

    private function isGradebookPage(): bool
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $route = $_GET['_route'] ?? '';

        return false !== strpos($scriptName, 'gradebook/index.php')
            || false !== strpos($requestUri, 'gradebook/index.php')
            || false !== strpos((string) $route, 'gradebook/index.php');
    }

    private function getPluginText(string $key, string $fallback): string
    {
        try {
            $translated = $this->get_lang($key);

            if (!empty($translated) && $translated !== $key) {
                return (string) $translated;
            }
        } catch (Throwable $exception) {
            return $fallback;
        }

        return $fallback;
    }

    private function getAlertHtml(string $message, string $type): string
    {
        $safeMessage = Security::remove_XSS($message);

        if ('error' === $type) {
            return '<div class="rounded-lg border border-danger bg-white px-4 py-3 text-center text-body-2 font-semibold text-danger">'
                .$safeMessage.
                '</div>';
        }

        return '<div class="rounded-lg border border-info bg-support-1 px-4 py-3 text-center text-body-2 font-semibold text-primary">'
            .$safeMessage.
            '</div>';
    }

    private function extractExtraFieldValue($value): ?string
    {
        if (empty($value) || !is_array($value)) {
            return null;
        }

        foreach (['field_value', 'value'] as $key) {
            if (array_key_exists($key, $value) && null !== $value[$key] && '' !== $value[$key]) {
                return (string) $value[$key];
            }
        }

        return null;
    }

    private function upsertExtraFieldValue(string $itemType, int $itemId, string $variable, string $value): bool
    {
        $extraField = new ExtraField($itemType);
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

        if (empty($fieldInfo['id'])) {
            return false;
        }

        $fieldId = (int) $fieldInfo['id'];
        $table = Database::get_main_table('extra_field_values');
        $columns = $this->getTableColumns($table);

        if (empty($columns)) {
            return false;
        }

        $attributes = [
            'field_id' => $fieldId,
            'item_id' => $itemId,
        ];

        if (isset($columns['field_value'])) {
            $attributes['field_value'] = $value;
        }

        if (isset($columns['value'])) {
            $attributes['value'] = $value;
        }

        if (!isset($attributes['field_value']) && !isset($attributes['value'])) {
            return false;
        }

        $sql = sprintf(
            'SELECT id FROM %s WHERE field_id = %d AND item_id = %d LIMIT 1',
            $table,
            $fieldId,
            $itemId
        );
        $result = Database::query($sql);
        $row = $result ? Database::fetch_assoc($result) : false;

        if (!empty($row['id'])) {
            $updateAttributes = $attributes;
            unset($updateAttributes['field_id'], $updateAttributes['item_id']);

            return false !== Database::update(
                $table,
                $updateAttributes,
                ['id = ?' => (int) $row['id']]
            );
        }

        return false !== Database::insert($table, $attributes);
    }

    private function getTableColumns(string $table): array
    {
        $columns = [];
        $result = Database::query('SHOW COLUMNS FROM '.$table);

        if (!$result) {
            return $columns;
        }

        while ($row = Database::fetch_assoc($result)) {
            if (!empty($row['Field'])) {
                $columns[$row['Field']] = true;
            }
        }

        return $columns;
    }
}
