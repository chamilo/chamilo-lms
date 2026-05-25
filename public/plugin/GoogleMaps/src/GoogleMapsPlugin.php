<?php

/* For licensing terms, see /license.txt */

/**
 * Google Maps plugin.
 */
class GoogleMapsPlugin extends Plugin
{
    public const MAX_EXTRA_FIELDS = 5;

    protected function __construct()
    {
        $this->isAdminPlugin = true;

        $parameters = [
            'enable_api' => 'boolean',
            'api_key' => 'text',
            'extra_field_name' => 'text',
        ];

        parent::__construct('1.1', 'José Loguercio Silva', $parameters);
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['is_admin_plugin'] = true;
        $info['supports_regions'] = false;

        return $info;
    }

    public function isGoogleApiEnabled(): bool
    {
        return in_array($this->get('enable_api'), [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    public function getApiKey(): string
    {
        return trim((string) $this->get('api_key'));
    }

    public function hasApiKey(): bool
    {
        return '' !== $this->getApiKey();
    }

    public function getMapUrl(): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'GoogleMaps/src/map_coordinates.php';
    }

    public function getAdminUrl(): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'GoogleMaps/admin.php';
    }

    /**
     * @return string[]
     */
    public function getConfiguredExtraFieldNames(): array
    {
        $rawValue = (string) $this->get('extra_field_name');
        $fieldNames = array_map('trim', explode(',', $rawValue));
        $fieldNames = array_filter($fieldNames, static function (string $fieldName): bool {
            if ('' === $fieldName) {
                return false;
            }

            return 1 === preg_match('/^[A-Za-z0-9_\-]+$/', $fieldName);
        });

        $fieldNames = array_values(array_unique($fieldNames));

        return array_slice($fieldNames, 0, self::MAX_EXTRA_FIELDS);
    }

    public function renderAdminSummary(): string
    {
        $apiStatus = $this->isGoogleApiEnabled() && $this->hasApiKey()
            ? $this->get_lang('Configured')
            : $this->get_lang('NotConfigured');

        $fields = $this->getConfiguredExtraFieldNames();
        $fieldsLabel = empty($fields)
            ? $this->get_lang('NotConfigured')
            : implode(', ', array_map('htmlspecialchars', $fields));

        $mapUrl = htmlspecialchars($this->getMapUrl());
        $configureUrl = api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?plugin=GoogleMaps';

        return '
            <section class="card">
                <div class="card-body">
                    <p class="text-muted">'.$this->get_lang('GoogleMapsAdminIntro').'</p>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg border p-4">
                            <div class="text-xs font-semibold uppercase text-gray-400">'.$this->get_lang('GoogleMapsApi').'</div>
                            <div class="font-semibold">'.htmlspecialchars($apiStatus).'</div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="text-xs font-semibold uppercase text-gray-400">'.$this->get_lang('ExtraFields').'</div>
                            <div class="font-semibold">'.$fieldsLabel.'</div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="text-xs font-semibold uppercase text-gray-400">'.$this->get_lang('Access').'</div>
                            <div class="font-semibold">'.$this->get_lang('AdministratorsOnly').'</div>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a class="btn btn--primary" href="'.$mapUrl.'">
                            <em class="mdi mdi-map-marker"></em> '.$this->get_lang('OpenMap').'
                        </a>
                        <a class="btn btn--secondary" href="'.htmlspecialchars($configureUrl).'">
                            <em class="mdi mdi-cog"></em> '.$this->get_lang('ConfigurePlugin').'
                        </a>
                    </div>
                </div>
            </section>
        ';
    }
}
