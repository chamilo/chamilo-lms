<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * chamidoc plugin\CStudio\0_dal\dal.vdatabase.php
 * Virtual class for Software.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 09/04/2026
 */
class VirtualDatabase
{
    // chamil = 1.11.x
    // chami2 = 2.0.x
    public string $engine = 'chamil';
    public ?int $lastidinsert = 0;

    public function __construct()
    {
        $this->detect_engine();
    }

    public function query($sql)
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return Database::query($sql);
        }
    }

    public function update($table, $params, $where): void
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            Database::update($table, $params, $where);
        }
    }

    public function insert($table, $params)
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return Database::insert($table, $params);
        }
    }

    public function insert_id()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return Database::insert_id();
        }
    }

    public function query_to_array($sql): array
    {
        $this->detect_engine();
        $result = [];

        if ('chamil' == $this->engine) {
            $resultDB = Database::query($sql);
            while ($PartLudi = Database::fetch_array($resultDB)) {
                $result[] = $PartLudi;
            }
        }

        return $result;
    }

    public function get_value_by_query($sql, $field)
    {
        $val = '';
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            $result = Database::query($sql);
            while ($PartLudi = Database::fetch_array($result)) {
                $val = $PartLudi[$field];
            }
        }

        return $val;
    }

    public function moo_correct_sql($sql)
    {
        global $CFG;
        $prefix = $CFG->prefix;
        $tableMoo = ' '.$prefix.'plugin_oel_tools_';
        $sql = str_replace(' plugin_oel_tools_', $tableMoo, $sql);
        $sql = str_replace(');', ')', $sql);

        return $sql;
    }

    // Detect data engine
    public function detect_engine(): void
    {
        $this->engine = 'chamil';
    }

    public function escape_string($string)
    {
        return $string;
    }

    public function get_course_table($term)
    {
        if ('chamil' == $this->engine) {
            return Database::get_course_table($term);
        }
    }

    public function get_main_table($term)
    {
        if ('chamil' == $this->engine) {
            return Database::get_main_table($term);
        }
    }

    public function remove_XSS($term)
    {
        $this->detect_engine();

        $term = str_replace(['<', '>', "'", '"', ')', '('], ['&lt;', '&gt;', '&apos;', '&#x22;', '&#x29;', '&#x28;'], $term);
        $term = str_replace(['?', '%', '!'], ['', '', ''], $term);

        return str_ireplace('%3Cscript', '', $term);
    }

    public function w_api_is_anonymous()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_is_anonymous();
        }
    }

    public function w_api_get_user_info()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_get_user_info();
        }
    }

    public function w_api_get_user_id()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_get_user_id();
        }
    }

    public function w_get_path($term)
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            if (WEB_PATH === $term) {
                return Container::getRouter()->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            return api_get_path($term);
        }
    }

    public function w_course_path($cid)
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return sprintf(
                '/course/%s/home?sid=0',
                (int) $cid
            );
        }
    }

    public function w_is_platform_admin()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_is_platform_admin();
        }
    }

    public function w_is_session_admin()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_is_session_admin();
        }
    }

    public function w_get_multiple_access_url()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_get_multiple_access_url();
        }
    }

    public function w_get_current_access_url_id()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_get_current_access_url_id();
        }
    }

    public function w_api_get_course_path()
    {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            $course = api_get_course_entity();

            return $course?->getDirectory() ?: '';
        }
    }

    public function w_api_get_language_isocode($language = null)
    {
        if ('en' == $language) {
            return 'en';
        }
        if ('FR-fr' == $language) {
            return 'fr';
        }
        if ('es' == $language) {
            return 'es';
        }

        return 'en';
    }

    /**
     * Returns the isocode for the current course's language (e.g. 'fr_FR', 'en_US').
     * Falls back to the platform default language, then 'en_US'.
     */
    public function w_api_get_course_locale(): string
    {
        $this->detect_engine();

        if ('chamil' === $this->engine) {
            $course = api_get_course_entity();
            if ($course) {
                $courseLang = $course->getCourseLanguage();
                if ('' !== $courseLang) {
                    $langTable = Database::get_main_table(TABLE_MAIN_LANGUAGE);
                    $safe = Database::escape_string($courseLang);
                    // english_name lookup (e.g. 'french' → 'fr_FR')
                    $result = Database::query(
                        "SELECT isocode FROM $langTable WHERE english_name = '$safe' LIMIT 1"
                    );
                    if ($row = Database::fetch_assoc($result)) {
                        return $row['isocode'];
                    }
                    // Already an isocode?
                    $result2 = Database::query(
                        "SELECT isocode FROM $langTable WHERE isocode = '$safe' LIMIT 1"
                    );
                    if ($row2 = Database::fetch_assoc($result2)) {
                        return $row2['isocode'];
                    }
                }
            }
            // Fall back to platform language
            $platformIso = api_get_platform_default_isocode();
            if (null !== $platformIso && '' !== $platformIso) {
                return $platformIso;
            }
        }

        return 'en_US';
    }
    public function w_api_is_allowed_to_edit() {
        $this->detect_engine();

        if ('chamil' == $this->engine) {
            return api_is_allowed_to_edit();
        }
    }

}
