<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

/**
 * Class SurveyExportTxtPlugin.
 */
class SurveyExportTxtPlugin extends Plugin
{
    /**
     * SurveyExportTxtPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
            'enabled' => 'boolean',
            'export_incomplete' => 'boolean',
        ];

        parent::__construct('0.1', 'Angel Fernando Quiroz Campos', $settings);
    }

    /**
     * @return SurveyExportTxtPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Installation process.
     */
    public function install()
    {
    }

    /**
     * Uninstallation process.
     */
    public function uninstall()
    {
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public static function filterModify($params)
    {
        $enabled = api_get_plugin_setting('surveyexporttxt', 'enabled');

        if ('true' !== $enabled) {
            return '';
        }

        $surveyId = isset($params['survey_id']) ? (int) $params['survey_id'] : 0;
        $iconSize = isset($params['icon_size']) ? $params['icon_size'] : ICON_SIZE_SMALL;

        if (empty($surveyId)) {
            return '';
        }

        return Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_ARCHIVE, 'ch-tool-icon', null, $iconSize, get_lang('Export')),
            api_get_path(WEB_PLUGIN_PATH).'SurveyExportTxt/export.php?survey='.$surveyId.'&'.api_get_cidreq()
        );
    }

    /**
     * Create tools for all courses.
     */
    private function createLinkToCourseTools()
    {
        $result = Database::getManager()
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')
            ->getResult();

        foreach ($result as $item) {
            $this->createLinkToCourseTool($this->get_name().':teacher', $item['id']);
        }
    }

    /**
     * Remove all course tools created by plugin.
     */
    private function removeLinkToCourseTools()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.link LIKE :link AND t.category = :category')
            ->execute(['link' => 'SurveyExportTxt/start.php%', 'category' => 'plugin']);
    }
}
