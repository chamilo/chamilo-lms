<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Dashboard plugin entry point for Chamilo 2.
 *
 * The old Dashboard directory contains legacy block classes from Chamilo 1.11.
 * They are intentionally kept as reference files, but this plugin now exposes a
 * small admin dashboard page that is safe to load in Chamilo 2.
 */
class DashboardPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '2.0.0',
            'Chamilo',
            []
        );

        $this->isAdminPlugin = true;
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function install(): void
    {
        // No database changes are required.
    }

    public function uninstall(): void
    {
        // No data is created by this plugin.
    }

    public function renderRegion($region)
    {
        if ('menu_administrator' !== $region) {
            return '';
        }

        if (!$this->isEnabled() || !api_is_platform_admin()) {
            return '';
        }

        $url = api_get_path(WEB_PLUGIN_PATH).'Dashboard/admin.php';
        $label = Security::remove_XSS($this->get_lang('plugin_title'));

        return '<a class="list-group-item" href="'.$url.'">'
            .'<span class="mdi mdi-view-dashboard-outline ch-tool-icon" aria-hidden="true"></span> '
            .$label
            .'</a>';
    }
}
