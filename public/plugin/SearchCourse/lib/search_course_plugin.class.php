<?php
/* For licensing terms, see /license.txt */

class SearchCoursePlugin extends Plugin
{
    public const PLUGIN_NAME = 'SearchCourse';

    protected function __construct()
    {
        parent::__construct('1.2', 'Laurent Opprecht');
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = false;

        return $info;
    }

    public function getSearchPageUrl(): string
    {
        return api_get_path(WEB_PATH).'plugin/'.self::PLUGIN_NAME.'/index.php';
    }

    public function renderRegion($region): string
    {
        return '';
    }

    public function isCurrentSearchCoursePage(): bool
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? '');
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        $pluginPage = '/plugin/'.self::PLUGIN_NAME.'/index.php';

        return str_contains($scriptName, $pluginPage)
            || str_contains($phpSelf, $pluginPage)
            || str_contains($requestUri, $pluginPage);
    }
}
