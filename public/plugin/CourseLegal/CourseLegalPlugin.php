<?php

/* For licensing terms, see /license.txt */

/**
 * Class CourseLegalPlugin.
 */
class CourseLegalPlugin extends Plugin
{
    public $isCoursePlugin = true;

    public $isAdminPlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
            ]
        );
    }

    /**
     * @return CourseLegalPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }


    public function getAdminUrl()
    {
        return api_get_path(WEB_PLUGIN_PATH).'CourseLegal/admin.php';
    }


    /**
     * @return string
     */
    public function getTeacherLink()
    {
        $link = null;
        if (api_is_allowed_to_edit()) {
            $url = api_get_path(WEB_PLUGIN_PATH).'CourseLegal/start.php?'.api_get_cidreq();
            $link = Display::url(
                $this->get_lang('CourseLegal'),
                $url,
                ['class' => 'btn btn--primary']
            );
        }

        return $link;
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public function getUserAcceptedLegal($userId, $courseId, $sessionId)
    {
        $userId = (int) $userId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $table = Database::get_main_table('session_rel_course_rel_user_legal');
        $sql = "SELECT *
                FROM $table
                WHERE user_id = $userId AND c_id = $courseId AND session_id = $sessionId";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            $data = Database::fetch_assoc($result);
        }

        return $data;
    }

    /**
     * @param int    $userId
     * @param string $courseCode
     * @param int    $sessionId
     *
     * @return bool
     */
    public function isUserAcceptedLegal($userId, $courseCode, $sessionId)
    {
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];
        $result = $this->getUserAcceptedLegal($userId, $courseId, $sessionId);

        if (!empty($result)) {
            return 1 === (int) $result['web_agreement'];
        }

        return false;
    }

    /**
     * @param int  $userId
     * @param int  $courseCode
     * @param int  $sessionId
     * @param bool $sendEmail  Optional. Indicate whether the mail confirmation link must be sent.
     */
    public function saveUserLegal($userId, $courseCode, $sessionId, $sendEmail = false)
    {
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];
        $data = $this->getUserAcceptedLegal($userId, $courseId, $sessionId);
        $table = Database::get_main_table('session_rel_course_rel_user_legal');
        $uniqueId = api_get_unique_id();

        if (empty($data)) {
            $values = [
                'user_id' => $userId,
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'web_agreement' => 1,
                'web_agreement_date' => api_get_utc_datetime(),
                'mail_agreement' => $sendEmail ? 0 : 1,
                'mail_agreement_date' => $sendEmail ? null : api_get_utc_datetime(),
                'mail_agreement_link' => $uniqueId,
            ];

            $id = Database::insert($table, $values);

            if ($sendEmail) {
                $this->sendMailLink($uniqueId, $userId, $courseId, $sessionId);
            }

            return $id;
        }

        Database::update(
            $table,
            [
                'web_agreement' => 1,
                'web_agreement_date' => api_get_utc_datetime(),
            ],
            ['id = ? ' => [$data['id']]]
        );

        if ($sendEmail) {
            $this->updateMailAgreementLink($userId, $courseId, $sessionId);
        }

        return (int) $data['id'];
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     */
    public function updateMailAgreementLink($userId, $courseId, $sessionId)
    {
        $data = $this->getUserAcceptedLegal($userId, $courseId, $sessionId);
        if (!empty($data)) {
            $table = Database::get_main_table(
                'session_rel_course_rel_user_legal'
            );
            $uniqueId = api_get_unique_id();
            Database::update(
                $table,
                ['mail_agreement_link' => $uniqueId],
                ['id = ? ' => [$data['id']]]
            );
            $this->sendMailLink($uniqueId, $userId, $courseId, $sessionId);
        }
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     */
    public function deleteUserAgreement($userId, $courseId, $sessionId)
    {
        $data = $this->getUserAcceptedLegal($userId, $courseId, $sessionId);
        if (!empty($data)) {
            $table = Database::get_main_table(
                'session_rel_course_rel_user_legal'
            );
            Database::delete(
                $table,
                ['id = ? ' => [$data['id']]]
            );
        }
    }

    /**
     * @param string $uniqueId
     * @param int    $userId
     * @param int    $courseId
     * @param int    $sessionId
     */
    public function sendMailLink($uniqueId, $userId, $courseId, $sessionId)
    {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];

        $url = api_get_path(WEB_CODE_PATH).'course_info/legal.php?web_agreement_link='.$uniqueId.'&course_code='.Security::remove_XSS($courseCode).'&session_id='.$sessionId;
        $courseUrl = Display::url($url, $url);
        $sessionInfo = api_get_session_info($sessionId);
        $sesstionTitle = null;

        if (!empty($sessionInfo)) {
            $sesstionTitle = ' ('.$sessionInfo['title'].')';
        }

        $courseTitle = $courseInfo['title'].$sesstionTitle;

        $subject = $this->get_lang('MailAgreement');
        $message = sprintf($this->get_lang('MailAgreementWasSentWithClickX'), $courseTitle, $courseUrl);
        MessageManager::send_message_simple($userId, $subject, $message);
    }

    /**
     * @param string $link
     * @param int    $userId
     * @param int    $courseId
     * @param int    $sessionId
     *
     * @return bool
     */
    public function saveUserMailLegal($link, $userId, $courseId, $sessionId)
    {
        $data = $this->getUserAcceptedLegal($userId, $courseId, $sessionId);

        if (empty($data)) {
            return null;
        }

        if ($data['mail_agreement_link'] == $link) {
            $table = Database::get_main_table('session_rel_course_rel_user_legal');
            $id = $data['id'];
            $values = [
                'mail_agreement' => 1,
                'mail_agreement_date' => api_get_utc_datetime(),
            ];
            Database::update($table, $values, ['id = ?' => [$id]]);
        }
    }

    /**
     * @param int    $courseId
     * @param int    $sessionId
     * @param string $filePath
     */
    public function warnUsersByEmail($courseId, $sessionId, $filePath = null)
    {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];

        if (empty($sessionId)) {
            $students = CourseManager::get_student_list_from_course_code($courseCode, false);
        } else {
            $students = CourseManager::get_student_list_from_course_code($courseCode, true, $sessionId);
        }

        $url = api_get_course_url($courseId, $sessionId);
        $url = Display::url($url, $url);

        $subject = $this->get_lang('AgreementUpdated');
        $message = sprintf($this->get_lang('AgreementWasUpdatedClickHere'), $url);

        $dataFile = [];
        if (!empty($filePath)) {
            $dataFile = [
                'path' => $filePath,
                'filename' => basename($filePath),
            ];
            $message = sprintf($this->get_lang('AgreementWasUpdatedClickHere'), $url)." \n";
            $message .= $this->get_lang('TheAgreementIsAttachedInThisEmail');
        }

        if (!empty($students)) {
            foreach ($students as $student) {
                $userInfo = api_get_user_info($student['user_id']);
                api_mail_html(
                    $userInfo['complete_name'],
                    $userInfo['email'],
                    $subject,
                    $message,
                    null,
                    null,
                    [],
                    $dataFile
                );
                //MessageManager::send_message_simple($student['user_id'], $subject, $message);
            }
        }
    }

    /**
     * @param int    $courseId
     * @param int    $sessionId
     * @param string $order
     *
     * @return array
     */
    public function getUserAgreementList($courseId, $sessionId, $order = null)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $table = Database::get_main_table('session_rel_course_rel_user_legal');
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT *
                FROM $table s INNER JOIN $userTable u
                ON u.id = s.user_id
                WHERE c_id = $courseId AND session_id = $sessionId ";

        if (!empty($order)) {
            $sql .= $order;
        }
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            $data = Database::store_result($result, 'ASSOC');
        }

        return $data;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     */
    public function removePreviousAgreements($courseId, $sessionId)
    {
        $table = Database::get_main_table('session_rel_course_rel_user_legal');
        $sessionId = (int) $sessionId;
        $courseId = (int) $courseId;
        $sql = "DELETE FROM $table
                WHERE c_id = '$courseId' AND session_id = $sessionId ";
        Database::query($sql);
    }

    /**
     * @param array $values
     * @param array $file       $_FILES['uploaded_file']
     * @param bool  $deleteFile
     */
    public function save($values, $file = [], $deleteFile = false)
    {
        $table = Database::get_main_table('session_rel_course_legal');

        $courseId = (int) $values['c_id'];
        $sessionId = (int) $values['session_id'];

        $conditions = [
            'c_id' => $courseId,
            'session_id' => $sessionId,
        ];

        $legalData = $this->getData($courseId, $sessionId);
        $fileName = null;
        $uploadedFilePath = null;

        if (!empty($file) && process_uploaded_file($file, false)) {
            $uploadedFilePath = $this->storeUploadedFile($courseId, $sessionId, $file);

            if ($uploadedFilePath) {
                $fileName = basename($uploadedFilePath);

                if (!empty($legalData['filename'])) {
                    $this->deleteStoredFile($courseId, $sessionId, $legalData['filename']);
                }
            }
        }

        $conditions['content'] = $values['content'];
        $conditions['filename'] = $fileName;

        if (empty($legalData)) {
            $id = Database::insert($table, $conditions);
        } else {
            $id = $legalData['id'];

            $updateParams = [
                'content' => $values['content'],
            ];

            if (!empty($fileName)) {
                $updateParams['filename'] = $fileName;
            }

            Database::update(
                $table,
                $updateParams,
                ['id = ? ' => $id]
            );
        }

        if ($deleteFile) {
            Database::update(
                $table,
                ['filename' => ''],
                ['id = ? ' => $id]
            );

            if (!empty($legalData['filename'])) {
                $this->deleteStoredFile($courseId, $sessionId, $legalData['filename']);
            }

            $fileName = null;
            $uploadedFilePath = null;
        }

        if (isset($values['remove_previous_agreements']) &&
            !empty($values['remove_previous_agreements'])
        ) {
            $this->removePreviousAgreements($courseId, $sessionId);
        }

        $warnUsers = isset($values['warn_users_by_email']) ? $values['warn_users_by_email'] : null;

        switch ($warnUsers) {
            case '1':
                // Nothing
                break;
            case '2':
                // Send mail
                $this->warnUsersByEmail($courseId, $sessionId);

                break;
            case '3':
                // Send mail + attachment if exists.
                $attachmentPath = $uploadedFilePath;

                if (empty($attachmentPath)) {
                    $currentData = $this->getData($courseId, $sessionId);
                    if (!empty($currentData['filename'])) {
                        $attachmentPath = $this->getStoredFilePath($courseId, $sessionId, $currentData['filename']);
                    }
                }

                if (!empty($attachmentPath) && is_file($attachmentPath)) {
                    $this->warnUsersByEmail(
                        $courseId,
                        $sessionId,
                        $attachmentPath
                    );
                }

                break;
        }
    }

    private function getStorageDirectory(int $courseId, int $sessionId): string
    {
        return rtrim(api_get_path(SYMFONY_SYS_PATH), '/').'/var/upload/course_legal/course_'.$courseId.'/session_'.$sessionId;
    }

    private function ensureStorageDirectory(int $courseId, int $sessionId): string
    {
        $directory = $this->getStorageDirectory($courseId, $sessionId);

        if (!is_dir($directory)) {
            mkdir($directory, api_get_permissions_for_new_directories(), true);
        }

        return $directory;
    }

    private function sanitizeUploadedFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        $filename = trim((string) $filename, '._-');

        if ('' === $filename) {
            $filename = 'agreement';
        }

        return date('YmdHis').'_'.uniqid('', true).'_'.$filename;
    }

    private function storeUploadedFile(int $courseId, int $sessionId, array $file): ?string
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $directory = $this->ensureStorageDirectory($courseId, $sessionId);
        $fileName = $this->sanitizeUploadedFilename($file['name'] ?? 'agreement');
        $targetPath = $directory.'/'.$fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        chmod($targetPath, api_get_permissions_for_new_files());

        return $targetPath;
    }

    public function getStoredFilePath(int $courseId, int $sessionId, string $fileName): string
    {
        return $this->getStorageDirectory($courseId, $sessionId).'/'.basename($fileName);
    }

    private function deleteStoredFile(int $courseId, int $sessionId, string $fileName): void
    {
        $filePath = $this->getStoredFilePath($courseId, $sessionId, $fileName);

        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array|mixed
     */
    public function getData($courseId, $sessionId)
    {
        $table = Database::get_main_table('session_rel_course_legal');
        $conditions = [
            'c_id  = ? AND session_id = ? ' => [
                $courseId,
                $sessionId,
            ],
        ];

        $result = Database::select('*', $table, ['where' => $conditions]);

        return isset($result) && !empty($result) ? current($result) : [];
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return string
     */
    public function getCurrentFile($courseId, $sessionId)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $data = $this->getData($courseId, $sessionId);

        if (isset($data['filename']) && !empty($data['filename'])) {
            $file = $this->getStoredFilePath($courseId, $sessionId, $data['filename']);

            if (file_exists($file)) {
                $url = api_get_path(WEB_PLUGIN_PATH).'CourseLegal/download.php?course_id='.$courseId.'&session_id='.$sessionId;

                return Display::url(
                    $data['filename'],
                    $url,
                    ['target' => '_blank']
                );
            }
        }

        return '';
    }

    public function install()
    {
        $table = Database::get_main_table('session_rel_course_legal');
        $sql = "CREATE TABLE IF NOT EXISTS $table (
                    id int PRIMARY KEY AUTO_INCREMENT,
                    c_id int,
                    session_id int,
                    content text,
                    filename varchar(255)
                )";
        Database::query($sql);

        $table = Database::get_main_table('session_rel_course_rel_user_legal');

        $sql = "CREATE TABLE IF NOT EXISTS $table (
                    id int PRIMARY KEY AUTO_INCREMENT,
                    user_id int,
                    c_id int,
                    session_id int,
                    web_agreement varchar(255),
                    web_agreement_date datetime,
                    mail_agreement varchar(255),
                    mail_agreement_date datetime,
                    mail_agreement_link varchar(255)
                )";
        Database::query($sql);
    }

    public function uninstall()
    {
        $table = Database::get_main_table('session_rel_course_legal');
        $sql = "DROP TABLE IF EXISTS $table ";
        Database::query($sql);

        $table = Database::get_main_table('session_rel_course_rel_user_legal');
        $sql = "DROP TABLE IF EXISTS $table ";
        Database::query($sql);
    }
}
