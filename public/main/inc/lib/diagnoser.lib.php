<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;

/**
 * Generates diagnostic information about the system.
 *
 * Notes:
 * - Adjusted to current path constants provided by the platform.
 * - Database section is DBAL-3 friendly (no getHost()).
 * - Courses space section prefers DB sum (resource_file) with safe fallbacks.
 */
class Diagnoser
{
    public const STATUS_OK = 1;
    public const STATUS_WARNING = 2;
    public const STATUS_ERROR = 3;
    public const STATUS_INFORMATION = 4;

    public function __construct() {}

    /**
     * Render diagnostics UI with Tailwind (no Bootstrap).
     * Drop-in replacement for show_html().
     */
    public function show_html(): void
    {
        // Section registry (label + short info)
        $sections = [
            'chamilo' => [
                'label' => 'Chamilo',
                'info' => 'State of Chamilo requirements',
                'icon' => 'mdi-cog-outline',
            ],
            'php' => [
                'label' => 'PHP',
                'info' => 'State of PHP settings on the server',
                'icon' => 'mdi-language-php',
            ],
            'database' => [
                'label' => 'Database',
                'info' => 'Database server configuration and metadata',
                'icon' => 'mdi-database',
            ],
            'webserver' => [
                'label' => get_lang('Web server'),
                'info' => 'Information about your webserver configuration',
                'icon' => 'mdi-server',
            ],
            'paths' => [
                'label' => 'Paths',
                'info' => 'api_get_path() constants resolved on this portal',
                'icon' => 'mdi-folder-outline',
            ],
            'courses_space' => [
                'label' => 'Courses space',
                'info' => 'Disk usage per course vs disk quota',
                'icon' => 'mdi-folder-cog-outline',
            ],
        ];

        $current = isset($_GET['section']) ? trim((string) $_GET['section']) : '';
        if (!array_key_exists($current, $sections)) {
            $current = 'chamilo';
        }

        // Header
        echo $this->tw_header(
            title: 'System status',
            subtitle: $sections[$current]['info']
        );

        // Icon cards navigation
        echo $this->tw_nav_cards($sections, $current);

        // Section notice
        echo $this->tw_notice($sections[$current]['info']);

        // Fetch data
        $method = 'get_'.$current.'_data';
        $data = call_user_func([$this, $method]);

        // Render per-section
        if ('paths' === $current) {
            // $data = ['headers' => [...], 'data' => [CONST => value, ...]]
            $headers = $data['headers'] ?? ['Path', 'constant'];
            $rows = [];
            foreach (($data['data'] ?? []) as $const => $value) {
                $rows[] = [$value, $const];
            }
            echo $this->tw_table(headers: $headers, rows: $rows, dense: false);
        } elseif ('courses_space' === $current) {
            // $data = list of rows: [homeLink, code, sizeMB, quotaMB, editLink, lastVisit, dirAbs]
            $headers = [
                '', get_lang('Course code'), 'Space used on disk (MB)',
                'Set max course space (MB)', get_lang('Edit'), get_lang('Latest visit'),
                get_lang('Current folder'),
            ];
            echo $this->tw_table(headers: $headers, rows: $data, dense: true);
        } else {
            // Generic 6-column dataset from build_setting()
            $headers = [
                '', get_lang('Section'), get_lang('Setting'),
                get_lang('Current'), get_lang('Expected'), get_lang('Comment'),
            ];
            echo $this->tw_table(headers: $headers, rows: $data, dense: true);
        }
    }

    /* ---------- Tailwind view helpers (pure HTML, no Bootstrap) ---------- */

    /**
     * Nice page header.
     */
    private function tw_header(string $title, string $subtitle): string
    {
        // Tailwind header with subtle divider
        return '
<div class="mb-6">
  <h1 class="text-2xl font-semibold text-gray-900">'.$this->e($title).'</h1>
  <p class="mt-1 text-sm text-gray-600">'.$this->e($subtitle).'</p>
  <div class="mt-4 h-px w-full bg-gray-30"></div>
</div>';
    }

    /**
     * Info/notice card.
     */
    private function tw_notice(string $text): string
    {
        // Blue info card
        return '
<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800">
  <div class="flex items-start gap-3">
    <i class="mdi mdi-information-outline text-xl leading-none"></i>
    <p class="text-sm">'.$text.'</p>
  </div>
</div>';
    }

    /**
     * Icon card navigation for sections.
     * Highlights current section with ring + bg.
     */
    private function tw_nav_cards(array $sections, string $current): string
    {
        $html = '<div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">';
        foreach ($sections as $key => $meta) {
            $active = $key === $current;
            $ring = $active ? 'ring-2 ring-primary/80 bg-primary/5' : 'ring-1 ring-gray-200 hover:ring-gray-300';
            $txt = $active ? 'text-primary' : 'text-gray-700 group-hover:text-gray-900';
            $badge = $active ? '<span class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">Active</span>' : '';
            $url = 'system_status.php?section='.$key;

            $html .= '
  <a href="'.$url.'" class="group block rounded-2xl bg-white p-4 shadow-sm hover:shadow-md transition '.$ring.'">
    <div class="flex items-center gap-3">
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-10 group-hover:bg-gray-30">
        <i class="mdi '.$this->e($meta['icon']).' text-2xl '.$txt.'"></i>
      </div>
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <h3 class="truncate text-sm font-semibold '.$txt.'">'.$this->e($meta['label']).'</h3>
          '.$badge.'
        </div>
        <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">'.$this->e($meta['info']).'</p>
      </div>
    </div>
  </a>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Tailwind table renderer.
     * - sticky header
     * - subtle row separators
     * - optional dense mode.
     */
    private function tw_table(array $headers, array $rows, bool $dense = true): string
    {
        $thPad = $dense ? 'px-3 py-2' : 'px-4 py-3';
        $tdPad = $dense ? 'px-3 py-2' : 'px-4 py-3';

        $html = '
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-gray-800">
      <thead class="bg-gray-20 text-left text-xs font-semibold text-gray-600">
        <tr>';

        foreach ($headers as $h) {
            $html .= '<th scope="col" class="sticky top-0 z-10 '.$thPad.'">'.$h.'</th>';
        }

        $html .= '
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">';

        foreach ($rows as $row) {
            $html .= '<tr class="hover:bg-gray-20">';
            foreach ($row as $cell) {
                // Allow HTML for icons/links already generated upstream
                $html .= '<td class="'.$tdPad.' align-top">'.(string) $cell.'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '
      </tbody>
    </table>
  </div>
</div>';

        return $html;
    }

    /**
     * Simple HTML escaper for plain strings.
     */
    private function e(string $v): string
    {
        return htmlspecialchars($v, \ENT_QUOTES, 'UTF-8');
    }

    /**
     * Paths section (robust to current constant set).
     * Fix: do NOT use $paths[api_get_path(WEB_PATH)] as array key anymore.
     *
     * @return array{headers:array<string>,data:array<string,string>}
     */
    public function get_paths_data()
    {
        // Keep this list in sync with the provided platform constants.
        $constNames = [
            // Relative helpers
            'REL_CODE_PATH',
            'REL_COURSE_PATH',
            'REL_HOME_PATH',

            // Registered path types for api_get_path()
            'WEB_PATH',
            'SYS_PATH',
            'SYMFONY_SYS_PATH',

            'REL_PATH',
            'WEB_COURSE_PATH',
            'WEB_CODE_PATH',
            'SYS_CODE_PATH',
            'SYS_LANG_PATH',
            'WEB_IMG_PATH',
            'WEB_CSS_PATH',
            'WEB_PUBLIC_PATH',
            'SYS_CSS_PATH',
            'SYS_PLUGIN_PATH',
            'WEB_PLUGIN_PATH',
            'WEB_PLUGIN_ASSET_PATH',
            'SYS_ARCHIVE_PATH',
            'WEB_ARCHIVE_PATH',
            'LIBRARY_PATH',
            'CONFIGURATION_PATH',
            'WEB_LIBRARY_PATH',
            'WEB_LIBRARY_JS_PATH',
            'WEB_AJAX_PATH',
            'SYS_TEST_PATH',
            'SYS_TEMPLATE_PATH',
            'SYS_PUBLIC_PATH',
            'SYS_FONTS_PATH',
        ];

        $list = [];
        foreach ($constNames as $name) {
            if (defined($name)) {
                $value = api_get_path(constant($name));
                if (false !== $value && null !== $value && '' !== $value) {
                    // Map CONSTANT => resolved value
                    $list[$name] = $value;
                }
            }
        }

        // Sort by resolved path for readability, preserving keys (constants)
        asort($list);

        return [
            'headers' => ['Path', 'constant'],
            'data' => $list,
        ];
    }

    /**
     * Chamilo requirements snapshot.
     *
     * @return array<int,array>
     */
    public function get_chamilo_data()
    {
        $array = [];
        $writable_folders = [
            api_get_path(SYS_ARCHIVE_PATH).'cache',
            api_get_path(SYS_PATH).'upload/users/',
        ];
        foreach ($writable_folders as $folder) {
            $writable = is_writable($folder);
            $status = $writable ? self::STATUS_OK : self::STATUS_ERROR;
            $array[] = $this->build_setting(
                $status,
                '[FILES]',
                get_lang('Is writable').': '.$folder,
                'http://be2.php.net/manual/en/function.is-writable.php',
                $writable,
                1,
                'yes_no',
                get_lang('The directory must be writable by the web server')
            );
        }

        $exists = file_exists(api_get_path(SYS_CODE_PATH).'install');
        $status = $exists ? self::STATUS_WARNING : self::STATUS_OK;
        $array[] = $this->build_setting(
            $status,
            '[FILES]',
            get_lang('The directory exists').': /install',
            'http://be2.php.net/file_exists',
            $exists,
            0,
            'yes_no',
            get_lang('The directory should be removed (it is no longer necessary)')
        );

        $app_version = api_get_setting('platform.chamilo_database_version');
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[DB]',
            'chamilo_database_version',
            '#',
            $app_version,
            0,
            null,
            'Chamilo DB version'
        );

        $access_url_id = api_get_current_access_url_id();

        if (1 === $access_url_id) {
            $size = '-';
            $message2 = '';

            if (api_is_windows_os()) {
                $message2 .= get_lang('The space used on disk cannot be measured properly on Windows-based systems.');
            } else {
                $dir = api_get_path(SYS_PATH);
                $du = exec('du -sh '.escapeshellarg($dir), $err);
                if (str_contains($du, "\t")) {
                    list($size, $none) = explode("\t", $du, 2);
                    unset($none);
                }

                $limit = get_hosting_limit($access_url_id, 'disk_space');
                if (null === $limit) {
                    $limit = 0;
                }

                $message2 .= sprintf(get_lang('Total space used by portal %s limit is %s MB'), $size, (string) $limit);
            }

            $array[] = $this->build_setting(
                self::STATUS_OK,
                '[FILES]',
                'hosting_limit_disk_space',
                '#',
                $size,
                0,
                null,
                $message2
            );
        }

        $new_version = '-';
        $new_version_status = '';
        $file = api_get_path(SYS_CODE_PATH).'install/version.php';
        if (is_file($file)) {
            @include $file;
        }
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[CONFIG]',
            get_lang('Version from the version file'),
            '#',
            $new_version.' '.$new_version_status,
            '-',
            null,
            get_lang('The version from the version.php file is updated with each version but only available if the main/install/ directory is present.')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[CONFIG]',
            get_lang('Version from the config file'),
            '#',
            api_get_configuration_value('system_version'),
            $new_version,
            null,
            get_lang('The version from the main configuration file shows on the main administration page, but has to be changed manually on upgrade.')
        );

        return $array;
    }

    /**
     * PHP settings snapshot.
     *
     * @return array<int,array>
     */
    public function get_php_data()
    {
        $array = [];

        $version = \PHP_VERSION;
        $status = $version > REQUIRED_PHP_VERSION ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[PHP]',
            'phpversion()',
            'https://php.net/manual/en/function.phpversion.php',
            \PHP_VERSION,
            '>= '.REQUIRED_PHP_VERSION,
            null,
            get_lang('PHP version')
        );

        $setting = ini_get('output_buffering');
        $req_setting = 1;
        $status = $setting >= $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'output_buffering',
            'https://php.net/manual/en/outcontrol.configuration.php#ini.output-buffering',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Output buffering setting is "On" for being enabled or "Off" for being disabled. This setting also may be enabled through an integer value (4096 for example) which is the output buffer size.')
        );

        $setting = ini_get('file_uploads');
        $req_setting = 1;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'file_uploads',
            'https://php.net/manual/en/ini.core.php#ini.file-uploads',
            $setting,
            $req_setting,
            'on_off',
            get_lang('File uploads indicate whether file uploads are authorized at all')
        );

        $setting = ini_get('magic_quotes_runtime');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'magic_quotes_runtime',
            'https://php.net/manual/en/ini.core.php#ini.magic-quotes-runtime',
            $setting,
            $req_setting,
            'on_off',
            get_lang('This is a highly unrecommended feature which converts values returned by all functions that returned external values to slash-escaped values. This feature should *not* be enabled.')
        );

        $setting = ini_get('safe_mode');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'safe_mode',
            'https://php.net/manual/en/ini.core.php#ini.safe-mode',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Safe mode is a deprecated PHP feature which (badly) limits the access of PHP scripts to other resources. It is recommended to leave it off.')
        );

        $setting = ini_get('register_globals');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'register_globals',
            'https://php.net/manual/en/ini.core.php#ini.register-globals',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to use the register globals feature or not. Using it represents potential security risks with this software.')
        );

        $setting = ini_get('short_open_tag');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'short_open_tag',
            'https://php.net/manual/en/ini.core.php#ini.short-open-tag',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to allow for short open tags to be used or not. This feature should not be used.')
        );

        $setting = ini_get('magic_quotes_gpc');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'magic_quotes_gpc',
            'https://php.net/manual/en/ini.core.php#ini.magic_quotes_gpc',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to automatically escape values from GET, POST and COOKIES arrays. A similar feature is provided for the required data inside this software, so using it provokes double slash-escaping of values.')
        );

        $setting = ini_get('display_errors');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'display_errors',
            'https://php.net/manual/en/ini.core.php#ini.display_errors',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Show errors on screen. Turn this on on development servers, off on production servers.')
        );

        $setting = ini_get('default_charset');
        if ('' == $setting) {
            $setting = null;
        }
        $req_setting = 'UTF-8';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'default_charset',
            'https://php.net/manual/en/ini.core.php#ini.default-charset',
            $setting,
            $req_setting,
            null,
            get_lang('The default character set to be sent when returning pages')
        );

        $setting = ini_get('max_execution_time');
        $req_setting = '300 ('.get_lang('minimum').')';
        $status = $setting >= 300 ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'max_execution_time',
            'https://php.net/manual/en/ini.core.php#ini.max-execution-time',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum time a script can take to execute. If using more than that, the script is abandoned to avoid slowing down other users.')
        );

        $setting = ini_get('max_input_time');
        $req_setting = '300 ('.get_lang('minimum').')';
        $status = $setting >= 300 ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'max_input_time',
            'https://php.net/manual/en/ini.core.php#ini.max-input-time',
            $setting,
            $req_setting,
            null,
            get_lang('The maximum time allowed for a form to be processed by the server. If it takes longer, the process is abandonned and a blank page is returned.')
        );

        $setting = ini_get('memory_limit');
        $req_setting = '>= '.REQUIRED_MIN_MEMORY_LIMIT.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_MEMORY_LIMIT) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'memory_limit',
            'https://php.net/manual/en/ini.core.php#ini.memory-limit',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum memory limit for one single script run. If the memory needed is higher, the process will stop to avoid consuming all the server\'s available memory and thus slowing down other users.')
        );

        $setting = ini_get('post_max_size');
        $req_setting = '>= '.REQUIRED_MIN_POST_MAX_SIZE.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_POST_MAX_SIZE) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'post_max_size',
            'https://php.net/manual/en/ini.core.php#ini.post-max-size',
            $setting,
            $req_setting,
            null,
            get_lang('This is the maximum size of uploads through forms using the POST method (i.e. classical file upload forms)')
        );

        $setting = ini_get('upload_max_filesize');
        $req_setting = '>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_UPLOAD_MAX_FILESIZE) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'upload_max_filesize',
            'https://php.net/manual/en/ini.core.php#ini.upload_max_filesize',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum volume of an uploaded file. This setting should, most of the time, be matched with the post_max_size variable.')
        );

        $setting = ini_get('upload_tmp_dir');
        $status = self::STATUS_OK;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'upload_tmp_dir',
            'https://php.net/manual/en/ini.core.php#ini.upload_tmp_dir',
            $setting,
            '',
            null,
            get_lang('The temporary upload directory is a space on the server where files are uploaded before being filtered and treated by PHP.')
        );

        $setting = ini_get('variables_order');
        $req_setting = 'GPCS';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'variables_order',
            'https://php.net/manual/en/ini.core.php#ini.variables-order',
            $setting,
            $req_setting,
            null,
            get_lang('The order of precedence of Environment, GET, POST, COOKIES and SESSION variables')
        );

        $setting = ini_get('session.gc_maxlifetime');
        $req_setting = '4320';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[SESSION]',
            'session.gc_maxlifetime',
            'https://php.net/manual/en/ini.core.php#session.gc-maxlifetime',
            $setting,
            $req_setting,
            null,
            get_lang('The session garbage collector maximum lifetime indicates which maximum time is given between two runs of the garbage collector.')
        );

        $setting = api_check_browscap() ? true : false;
        $req_setting = true;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'browscap',
            'https://php.net/manual/en/misc.configuration.php#ini.browscap',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Browscap loading browscap.ini file that contains a large amount of data on the browser and its capabilities, so it can be used by the function get_browser () PHP')
        );

        // Extensions
        $extensions = [
            'curl' => ['link' => 'https://php.net/curl', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'exif' => ['link' => 'https://www.php.net/exif', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'fileinfo' => ['link' => 'https://php.net/fileinfo', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'gd' => ['link' => 'https://php.net/gd', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'ldap' => ['link' => 'https://php.net/ldap', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'mbstring' => ['link' => 'https://www.php.net/mbstring', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'pcre' => ['link' => 'https://php.net/pcre', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'pdo_mysql' => ['link' => 'https://php.net/manual/en/ref.pdo-mysql.php', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'session' => ['link' => 'https://php.net/session', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'standard' => ['link' => 'https://php.net/spl', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'zlib' => ['link' => 'https://php.net/zlib', 'expected' => 1, 'comment' => get_lang('This extension must be loaded.')],
            'apcu' => ['link' => 'https://php.net/apcu', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
            'bcmath' => ['link' => 'https://php.net/bcmath', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
            'Zend OPcache' => ['link' => 'https://php.net/opcache', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
            'openssl' => ['link' => 'https://php.net/openssl', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
            'xsl' => ['link' => 'https://php.net/xsl', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
            'xapian' => ['link' => 'https://xapian.org/docs/bindings/php/', 'expected' => 2, 'comment' => get_lang('This extension should be loaded.')],
        ];

        foreach ($extensions as $extension => $data) {
            $url = $data['link'];
            $expected_value = $data['expected'];
            $comment = $data['comment'];

            $loaded = extension_loaded($extension);
            $status = $loaded ? self::STATUS_OK : self::STATUS_ERROR;
            $array[] = $this->build_setting(
                $status,
                '[EXTENSION]',
                get_lang('Extension loaded').': '.$extension,
                $url,
                $loaded,
                $expected_value,
                'yes_no_optional',
                $comment
            );
        }

        $setting = ini_get('opcache.enable');
        $req_setting = '1';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[opcache]',
            'opcache.enable',
            'https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.enable',
            $setting,
            $req_setting,
            null,
            get_lang('The OPcache module needs to be both installed and enabled in order for the platform to use it.')
        );

        $setting = ini_get('opcache.memory_consumption');
        $req_limit = 128;
        $req_setting = '>= '.$req_limit.'M';
        $status = self::STATUS_INFORMATION;
        if ((int) $setting >= $req_limit) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[opcache]',
            'opcache.memory_consumption',
            'https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.memory-consumption',
            $setting,
            $req_setting,
            null,
            sprintf(get_lang('Allowing OPCache memory_consumption to reach %sMB of RAM at least is recommended.'), $req_limit)
        );

        $setting = 0;
        if (function_exists('apcu_enabled')) {
            $setting = (int) apcu_enabled();
        }
        $req_setting = 1;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_INFORMATION;
        $array[] = $this->build_setting(
            $status,
            '[apcu]',
            'apc.enabled',
            'https://www.php.net/manual/en/function.apcu-enabled.php',
            $setting,
            $req_setting,
            null,
            get_lang('The APCu extension needs to be both installed and enabled in order for the platform to use it.')
        );

        return $array;
    }

    /**
     * Database diagnostics (DBAL-3 friendly).
     * Fix: avoid Connection::getHost(); rely on params + platform.
     *
     * @return array<int,array>
     */
    public function get_database_data()
    {
        $array = [];
        $em = Database::getManager();
        $connection = $em->getConnection();

        // Prefer platform name (mysql, postgresql, sqlite, …)
        try {
            $driver = $connection->getDatabasePlatform()->getName();
        } catch (Throwable $e) {
            $driver = (method_exists($connection, 'getDriver') && method_exists($connection->getDriver(), 'getName'))
                ? $connection->getDriver()->getName()
                : 'unknown';
        }

        $params = method_exists($connection, 'getParams') ? (array) $connection->getParams() : [];
        $primary = isset($params['primary']) && is_array($params['primary']) ? $params['primary'] : $params;

        $host = $primary['host'] ?? ($primary['unix_socket'] ?? 'localhost');
        $port = $primary['port'] ?? null;

        try {
            $db = $connection->getDatabase();
        } catch (Throwable $e) {
            $db = $primary['dbname'] ?? ($primary['path'] ?? 'unknown');
        }

        $array[] = $this->build_setting(self::STATUS_INFORMATION, '[Database]', 'driver', '', $driver, null, null, get_lang('Driver'));
        $array[] = $this->build_setting(self::STATUS_INFORMATION, '[Database]', 'host', '', $host, null, null, get_lang('MySQL server host'));
        $array[] = $this->build_setting(self::STATUS_INFORMATION, '[Database]', 'port', '', (string) $port, null, null, get_lang('Port'));
        $array[] = $this->build_setting(self::STATUS_INFORMATION, '[Database]', 'Database name', '', $db, null, null, get_lang('Name'));

        return $array;
    }

    /**
     * Webserver snapshot.
     *
     * @return array<int,array>
     */
    public function get_webserver_data()
    {
        $array = [];

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_NAME"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['SERVER_NAME'] ?? '',
            null,
            null,
            get_lang('Server name (as used in your request)')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_ADDR"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['SERVER_ADDR'] ?? '',
            null,
            null,
            get_lang('Server address')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_PORT"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['SERVER_PORT'] ?? '',
            null,
            null,
            get_lang('Server port')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_SOFTWARE"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['SERVER_SOFTWARE'] ?? '',
            null,
            null,
            get_lang('Software running as a web server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["REMOTE_ADDR"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['REMOTE_ADDR'] ?? '',
            null,
            null,
            get_lang('Remote address (your address as received by the server)')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["HTTP_USER_AGENT"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            null,
            null,
            get_lang('Your user agent as received by the server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_PROTOCOL"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER['SERVER_PROTOCOL'] ?? '',
            null,
            null,
            get_lang('Protocol used by this server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            'php_uname()',
            'http://be2.php.net/php_uname',
            php_uname(),
            null,
            null,
            get_lang('Information on the system the current server is running on')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["HTTP_X_FORWARDED_FOR"]',
            'http://be.php.net/reserved.variables.server',
            !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
            null,
            null,
            get_lang('If the server is behind a proxy or firewall (and only in those cases), it might be using the X_FORWARDED_FOR HTTP header to show the remote user IP (yours, in this case).')
        );

        return $array;
    }

    /**
     * Return "Courses space" rows using DB sums (no filesystem scan).
     * Columns (legacy order):
     * [ homeLink, code, usedMB, quotaMB, editLink, last_visit, absPathHint ].
     *
     * v2 notes:
     * - There is no per-course public folder anymore.
     * - We compute sizes from ResourceFile (rf.size) linked through ResourceNode/ResourceLink.
     * - Use rl.c_id (NOT rl.course_id).
     * - We de-duplicate per (course_id, rf.id) to avoid double counting in the same course.
     * - We do NOT include Asset (global) files as they are not course-scoped.
     */
    public function get_courses_space_data()
    {
        $rows = [];

        $em = Database::getManager();
        $conn = $em->getConnection();

        // Aggregate used bytes from ResourceFile (no FS scan).
        $sql = <<<'SQL'
        SELECT
            c.id,
            c.code,
            c.disk_quota,
            c.last_visit,
            COALESCE(SUM(u.size), 0) AS used_bytes
        FROM course c
        LEFT JOIN (
            SELECT DISTINCT
                rl.c_id   AS course_id,
                rf.id     AS rf_id,
                rf.size   AS size
            FROM resource_link rl
            INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
            INNER JOIN resource_file rf ON rf.resource_node_id = rn.id
        ) u ON u.course_id = c.id
        GROUP BY c.id, c.code, c.disk_quota, c.last_visit
        ORDER BY c.last_visit DESC, c.code ASC
        LIMIT 1000
    SQL;

        $data = $conn->executeQuery($sql)->fetchAllAssociative();

        // Icons (no dead links in v2)
        $homeIcon = Display::getMdiIcon(ObjectIcon::HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Course homepage'));
        $editIcon = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'));
        $editBase = api_get_path(WEB_CODE_PATH).'admin/course_edit.php?id=';

        // v2 has no per-course absolute folder; provide a neutral hint.
        $storageHint = 'resource storage (v2 via Vich/Flysystem)';

        // Resolve platform default course quota once (MB)
        $defaultQuotaMb = $this->resolveDefaultCourseQuotaMb();

        foreach ($data as $row) {
            // Used bytes -> MB, min 1MB if > 0 to keep legacy semantics
            $bytes = (int) ($row['used_bytes'] ?? 0);
            $usedMb = $bytes > 0 ? max(1, (int) ceil($bytes / (1024 * 1024))) : 0;

            // Quota: per-course override if set (>0) else platform default (MB)
            // c.disk_quota is stored in BYTES; convert to MB if >0
            $quotaMb = ((int) $row['disk_quota'] > 0)
                ? (int) $row['disk_quota']
                : $defaultQuotaMb;

            $homeLink = $homeIcon; // no href by default in v2
            $editLink = '<a href="'.$editBase.(int) $row['id'].'">'.$editIcon.'</a>';

            $rows[] = [
                $homeLink,
                $row['code'],
                $usedMb,
                $quotaMb,
                $editLink,
                $row['last_visit'],
                $storageHint,
            ];
        }

        return $rows;
    }

    /**
     * Resolve the platform's default course quota in MB (robust).
     * Tries, in order:
     *  1) SettingsManager (v2)
     *  2) api_get_setting() (legacy)
     *  3) DB table settings (direct)
     *  4) DocumentManager::get_course_quota() fallback.
     */
    private function resolveDefaultCourseQuotaMb(): int
    {
        // 1) v2 SettingsManager (if available)
        try {
            if (class_exists('Container') && method_exists('Container', 'getSettingsManager')) {
                $sm = Container::getSettingsManager();
                if ($sm) {
                    $candidates = [
                        'course.course_quota',                 // expected v2 key
                        'document.default_course_quota',       // legacy-friendly
                        'document.default_document_quota',
                        'document.default_document_quotum',    // v1 spelling
                    ];
                    foreach ($candidates as $key) {
                        $raw = (string) $sm->getSetting($key);
                        if ('' !== $raw && '0' !== $raw && null !== $raw) {
                            $mb = $this->parseQuotaRawToMb($raw);
                            if ($mb >= 0) {
                                return $mb;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignore and continue with other strategies
        }

        // 2) api_get_setting() (legacy accessor)
        $candidates = [
            'course.course_quota',
            'document.default_course_quota',
            'document.default_document_quota',
            'document.default_document_quotum',
        ];
        foreach ($candidates as $key) {
            $raw = api_get_setting($key);
            if (false !== $raw && null !== $raw && '' !== $raw) {
                $mb = $this->parseQuotaRawToMb((string) $raw);
                if ($mb >= 0) {
                    return $mb;
                }
            }
        }

        // 3) Direct DB read from settings (works on most v1/v2 installs)
        try {
            $em = Database::getManager();
            $conn = $em->getConnection();

            foreach ($candidates as $key) {
                $val = $conn->fetchOne(
                    'SELECT value FROM settings WHERE variable = ? LIMIT 1',
                    [$key]
                );
                if (false !== $val && null !== $val && '' !== $val) {
                    $mb = $this->parseQuotaRawToMb((string) $val);
                    if ($mb >= 0) {
                        return $mb;
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignore and continue
        }

        // 4) Last resort: ask DocumentManager (may return platform default)
        try {
            if (class_exists('DocumentManager') && method_exists('DocumentManager', 'get_course_quota')) {
                $v = DocumentManager::get_course_quota(); // usually returns MB
                if (is_numeric($v)) {
                    $mb = $this->parseQuotaRawToMb((string) $v);
                    if ($mb >= 0) {
                        return $mb;
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignore
        }

        // Nothing found => keep legacy semantics (0 = no explicit default)
        return 0;
    }

    /**
     * Parse a quota raw value into MB.
     * Accepts:
     *  - "500"               -> 500 MB
     *  - "1G", "1GB", "1 g"  -> 1024 MB
     *  - "200M", "200MB"     -> 200 MB
     *  - large integers      -> assumed BYTES, converted to MB
     *  - strings with noise  -> extracts digits & unit heuristically.
     */
    private function parseQuotaRawToMb(string $raw): int
    {
        $s = strtolower(trim($raw));

        // Pure integer?
        if (preg_match('/^\d+$/', $s)) {
            $num = (int) $s;

            // Heuristic: if looks like bytes (>= 1MB in bytes), convert to MB.
            return ($num >= 1048576) ? (int) ceil($num / 1048576) : $num;
        }

        // <number><unit> where unit is m/mb or g/gb
        if (preg_match('/^\s*(\d+)\s*([mg])(?:b)?\s*$/i', $s, $m)) {
            $num = (int) $m[1];
            $unit = strtolower($m[2]);

            return 'g' === $unit ? $num * 1024 : $num;
        }

        // Extract digits for numbers hidden inside strings (e.g. "500 MB", "524288000 bytes", etc.)
        if (preg_match('/(\d+)/', $s, $m)) {
            $num = (int) $m[1];

            return ($num >= 1048576) ? (int) ceil($num / 1048576) : $num;
        }

        // Unknown
        return 0;
    }

    /**
     * Count courses (simple and fast).
     */
    public function get_courses_space_count(): int
    {
        $em = Database::getManager();
        $conn = $em->getConnection();

        $sql = 'SELECT COUNT(*) AS cnt FROM course';

        return (int) $conn->executeQuery($sql)->fetchOne();
    }

    /**
     * Helper to normalize a diagnostic row.
     *
     * @param int         $status
     * @param string      $section
     * @param string      $title
     * @param string      $url
     * @param mixed       $current_value
     * @param mixed       $expected_value
     * @param string|null $formatter
     * @param string      $comment
     *
     * @return array
     */
    public function build_setting(
        $status,
        $section,
        $title,
        $url,
        $current_value,
        $expected_value,
        $formatter,
        $comment
    ) {
        switch ($status) {
            case self::STATUS_OK:
                $img = StateIcon::COMPLETE;

                break;

            case self::STATUS_WARNING:
                $img = StateIcon::WARNING;

                break;

            case self::STATUS_ERROR:
                $img = StateIcon::ERROR;

                break;

            case self::STATUS_INFORMATION:
            default:
                $img = ActionIcon::INFORMATION;

                break;
        }

        $image = Display::getMdiIcon($img, 'ch-tool-icon', null, ICON_SIZE_SMALL, $title);
        $url = $this->get_link($title, $url);

        $formatted_current_value = $current_value;
        $formatted_expected_value = $expected_value;

        if ($formatter) {
            if (method_exists($this, 'format_'.$formatter)) {
                $formatted_current_value = call_user_func([$this, 'format_'.$formatter], $current_value);
                $formatted_expected_value = call_user_func([$this, 'format_'.$formatter], $expected_value);
            }
        }

        return [$image, $section, $url, $formatted_current_value, $formatted_expected_value, $comment];
    }

    /**
     * Create an anchor HTML element.
     *
     * @param string $title
     * @param string $url
     *
     * @return string
     */
    public function get_link($title, $url)
    {
        // Use about:blank (the legacy had a typo "about:bank")
        return '<a href="'.$url.'" target="about:blank">'.$title.'</a>';
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function format_yes_no_optional($value)
    {
        $return = '';

        switch ($value) {
            case 0:
                $return = get_lang('No');

                break;

            case 1:
                $return = get_lang('Yes');

                break;

            case 2:
                $return = get_lang('Optional');

                break;
        }

        return $return;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function format_yes_no($value)
    {
        return $value ? get_lang('Yes') : get_lang('No');
    }

    /**
     * @param int $value
     *
     * @return string|int
     */
    public function format_on_off($value)
    {
        $value = (int) $value;
        if ($value > 1) {
            // Greater than 1 values are shown "as-is", they may be interpreted as "On" later.
            return $value;
        }

        // 'On'/'Off' as in php.ini; not translated.
        return $value ? 'On' : 'Off';
    }
}
