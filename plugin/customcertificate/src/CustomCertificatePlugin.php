<?php
/* For license terms, see /license.txt */

/**
 * Plugin class for the CustomCertificate plugin.
 *
 * @package chamilo.plugin.customcertificate
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

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Jose Angel Ruiz - NoSoloRed (original author)',
            [
                'enable_plugin_customcertificate' => 'boolean',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @return CustomCertificatePlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        //Installing course settings
        $this->install_course_fields_in_all_courses();

        $tablesToBeCompared = [self::TABLE_CUSTOMCERTIFICATE];
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH).'customcertificate/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses($this->course_settings);

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
        $base = api_get_path(WEB_UPLOAD_PATH);
        if (Database::num_rows(Database::query("SHOW TABLES LIKE '$oldCertificateTable'")) == 1) {
            $sql = "SELECT * FROM $oldCertificateTable";
            $res = Database::query($sql);
            while ($row = Database::fetch_assoc($res)) {
                $pathOrigin = $base.'certificates/'.$row['id'].'/';
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

                Database::insert(self::TABLE_CUSTOMCERTIFICATE, $params);
                $certificateId = Database::insert_id();

                //Image manager
                $pathDestiny = $base.'certificates/'.$certificateId.'/';

                if (!file_exists($pathDestiny)) {
                    mkdir($pathDestiny, api_get_permissions_for_new_directories(), true);
                }

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
                        copy(
                            $pathOrigin.$row[$value],
                            $pathDestiny.$row[$value]
                        );
                    }
                }

                if ($row['certificate_default'] == 1) {
                    $params['c_id'] = 0;
                    $params['session_id'] = 0;
                    $params['certificate_default'] = 1;
                    Database::insert(self::TABLE_CUSTOMCERTIFICATE, $params);
                    $certificateId = Database::insert_id();
                    $pathOrigin = $base.'certificates/default/';
                    $pathDestiny = $base.'certificates/'.$certificateId.'/';
                    foreach ($imgList as $value) {
                        if (!empty($row[$value])) {
                            copy(
                                $pathOrigin.$row[$value],
                                $pathDestiny.$row[$value]
                            );
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
     * Get certificate data.
     *
     * @param Certificate $id The certificate
     *
     * @return array
     */
    public function getCertificateData($id)
    {
        $id = (int) $id;
        if (empty($id)) {
            return false;
        }

        $certificateTable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $categoryTable = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT cer.user_id AS user_id, cat.session_id AS session_id, cat.course_code AS course_code
                FROM $certificateTable cer
                INNER JOIN $categoryTable cat
                ON (cer.cat_id = cat.id)
                WHERE cer.id = $id";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_assoc($rs);
            $courseCode = $row['course_code'];
            $sessionId = $row['session_id'];
            $userId = $row['user_id'];
            if (api_get_course_setting('customcertificate_course_enable', $courseCode) == 1) {
                return [
                    'course_code' => $courseCode,
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                ];
            }
        }

        return [];
    }

    /**
     * Check if it redirects.
     *
     * @param certificate $certificate
     * @param int         $id
     *
     * @return array
     */
    public function redirectCheck($certificate, $certId)
    {
        $certId = (int) $certId;
        if (api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') == 'true') {
            $infoCertificate = CustomCertificatePlugin::getCertificateData($certId);
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

                $url = api_get_path(WEB_PLUGIN_PATH).'customcertificate/src/print_certificate.php'.
                    '?student_id='.$infoCertificate['user_id'].
                    '&course_code='.$infoCertificate['course_code'].
                    '&session_id='.$infoCertificate['session_id'];
                header('Location: '.$url);
            }
        }
    }
}
