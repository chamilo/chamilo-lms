<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

final class LegacyPluginCourseTool extends AbstractPlugin
{
    private string $link = '';

    public function __construct(
        string $title,
        string $titleToShow,
        string $link,
        string $icon = 'mdi-puzzle-outline',
        string $image = 'sessions_category.png'
    ) {
        $this->title = $title;
        $this->titleToShow = $titleToShow;
        $this->link = $link;
        $this->icon = $icon;
        $this->image = $image;
        $this->scope = '01';
    }

    public static function fromLegacyPlugin(\Plugin $plugin, string $courseToolTitle = ''): self
    {
        $pluginName = $plugin->get_name();
        $titleToShow = trim($courseToolTitle);

        if ('' === $titleToShow || 0 === strcasecmp($titleToShow, $pluginName)) {
            $titleToShow = $plugin->get_title() ?: $pluginName;
        }

        $sysPluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/';
        $webPluginPath = api_get_path(WEB_PLUGIN_PATH).$pluginName.'/';

        $link = '';
        foreach (['start.php', 'index.php', 'admin.php'] as $file) {
            if (is_file($sysPluginPath.$file)) {
                $link = $webPluginPath.$file;
                break;
            }
        }

        if ('' === $link) {
            $link = $webPluginPath;
        }

        return new self(
            $pluginName,
            $titleToShow,
            $link
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTitleToShow(): string
    {
        return $this->titleToShow;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
