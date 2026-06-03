<?php

/* For licensing terms, see /license.txt */

/**
 * Google Maps plugin.
 */
class GoogleMapsPlugin extends Plugin
{
    public const MAX_EXTRA_FIELDS = 5;
    public const PROVIDER_GOOGLE_MAPS = 'google_maps';
    public const PROVIDER_OPENSTREETMAP = 'openstreetmap';

    protected function __construct()
    {
        $this->isAdminPlugin = true;

        $parameters = [
            'map_provider' => [
                'type' => 'select',
                'options' => [
                    self::PROVIDER_GOOGLE_MAPS => 'GoogleMapsProvider',
                    self::PROVIDER_OPENSTREETMAP => 'OpenStreetMapProvider',
                ],
                'translate_options' => true,
            ],
            'enable_api' => 'boolean',
            'api_key' => 'text',
            'extra_field_name' => 'text',
            'default_latitude' => 'text',
            'default_longitude' => 'text',
            'default_zoom' => 'text',
        ];

        parent::__construct('1.2', 'José Loguercio Silva', $parameters);
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


    public function performActionsAfterConfigure()
    {
        $this->ensureConfiguredUserExtraFields();

        return $this;
    }

    public function ensureConfiguredUserExtraFields(): void
    {
        $fieldNames = $this->getConfiguredExtraFieldNames();

        if (empty($fieldNames)) {
            return;
        }

        $extraFieldLib = api_get_path(SYS_CODE_PATH).'inc/lib/extra_field.lib.php';
        if (is_file($extraFieldLib)) {
            require_once $extraFieldLib;
        }

        if (!class_exists('ExtraField')) {
            return;
        }

        $extraField = new ExtraField('user');

        foreach ($fieldNames as $fieldName) {
            $existingField = $extraField->get_handler_field_info_by_field_variable($fieldName);

            if (!empty($existingField)) {
                continue;
            }

            $extraField->save([
                'variable' => $fieldName,
                'display_text' => ucwords(str_replace(['_', '-'], ' ', $fieldName)),
                'value_type' => $this->resolveGeoExtraFieldType(),
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function resolveGeoExtraFieldType(): int
    {
        if (defined('ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES')) {
            return constant('ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES');
        }

        if (defined('ExtraField::FIELD_TYPE_GEOLOCALIZATION')) {
            return constant('ExtraField::FIELD_TYPE_GEOLOCALIZATION');
        }

        if (defined('ExtraField::FIELD_TYPE_TEXT')) {
            return constant('ExtraField::FIELD_TYPE_TEXT');
        }

        return 1;
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['is_admin_plugin'] = true;
        $info['supports_regions'] = false;

        return $info;
    }

    public function getMapProvider(): string
    {
        $provider = (string) $this->get('map_provider');

        if (in_array($provider, [self::PROVIDER_GOOGLE_MAPS, self::PROVIDER_OPENSTREETMAP], true)) {
            return $provider;
        }

        return self::PROVIDER_GOOGLE_MAPS;
    }

    public function getMapProviderLabel(): string
    {
        return match ($this->getMapProvider()) {
            self::PROVIDER_OPENSTREETMAP => $this->get_lang('OpenStreetMapProvider'),
            default => $this->get_lang('GoogleMapsProvider'),
        };
    }

    public function isGoogleMapsProvider(): bool
    {
        return self::PROVIDER_GOOGLE_MAPS === $this->getMapProvider();
    }

    public function isOpenStreetMapProvider(): bool
    {
        return self::PROVIDER_OPENSTREETMAP === $this->getMapProvider();
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

    public function isProviderConfigured(): bool
    {
        if ($this->isGoogleMapsProvider()) {
            return $this->isGoogleApiEnabled() && $this->hasApiKey();
        }

        return $this->isOpenStreetMapProvider();
    }

    public function getDefaultLatitude(): float
    {
        $value = trim((string) $this->get('default_latitude'));

        return is_numeric($value) ? (float) $value : 20.0;
    }

    public function getDefaultLongitude(): float
    {
        $value = trim((string) $this->get('default_longitude'));

        return is_numeric($value) ? (float) $value : 0.0;
    }

    public function getDefaultZoom(): int
    {
        $value = trim((string) $this->get('default_zoom'));

        if (is_numeric($value)) {
            $zoom = (int) $value;

            return max(1, min(20, $zoom));
        }

        return 2;
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
        $provider = htmlspecialchars($this->getMapProviderLabel());
        $apiStatus = $this->isProviderConfigured()
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
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-lg border p-4">
                            <div class="text-xs font-semibold uppercase text-gray-400">'.$this->get_lang('MapProvider').'</div>
                            <div class="font-semibold">'.$provider.'</div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="text-xs font-semibold uppercase text-gray-400">'.$this->get_lang('MapProviderConfiguration').'</div>
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
