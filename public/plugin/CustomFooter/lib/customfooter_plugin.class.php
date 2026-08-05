<?php

/* For licensing terms, see /license.txt */

/**
 * Custom footer plugin.
 *
 * This plugin renders a configurable two-column footer block in the global
 * pre_footer plugin region.
 */
class CustomFooterPlugin extends Plugin
{
    protected function __construct()
    {
        $settings = [
            'footer_left' => 'wysiwyg',
            'footer_right' => 'wysiwyg',
        ];

        parent::__construct('1.1', 'Valery Fremaux, Julio Montoya', $settings);
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = true;

        return $info;
    }

    public function renderRegion($region): string
    {
        if ('pre_footer' !== $region) {
            return '';
        }

        if (!$this->isEnabled(true)) {
            return '';
        }

        $left = $this->getFooterContent('footer_left');
        $right = $this->getFooterContent('footer_right');

        if ('' === $left && '' === $right) {
            return '';
        }

        $leftHtml = '' !== $left
            ? '<div class="custom-footer__column custom-footer__column--left">'.$left.'</div>'
            : '<div class="custom-footer__column custom-footer__column--left"></div>';

        $rightHtml = '' !== $right
            ? '<div class="custom-footer__column custom-footer__column--right">'.$right.'</div>'
            : '<div class="custom-footer__column custom-footer__column--right"></div>';

        return '
            <section class="custom-footer my-6 rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    '.$leftHtml.'
                    '.$rightHtml.'
                </div>
            </section>
        ';
    }

    private function getFooterContent(string $name): string
    {
        $value = $this->get($name);

        if (null === $value || '' === trim((string) $value)) {
            $value = $this->getLegacyFooterContent($name);
        }

        $value = trim((string) $value);

        if ('' === $value) {
            return '';
        }

        return Security::remove_XSS($value);
    }

    private function getLegacyFooterContent(string $name): string
    {
        $legacyName = 'customfooter_'.$name;

        try {
            $legacySettings = api_get_settings_params([
                'variable = ? ' => $legacyName,
                ' AND category = ? ' => 'Plugins',
            ]);

            foreach ($legacySettings as $setting) {
                if (isset($setting['selected_value'])) {
                    return (string) $setting['selected_value'];
                }
            }
        } catch (Throwable $e) {
            error_log('[CustomFooter] Unable to read legacy setting '.$legacyName.': '.$e->getMessage());
        }

        return '';
    }

    public function pix_url($pixname, $size = 16)
    {
        global $_configuration;

        if (file_exists(
            $_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.png'
        )) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.png';
        }
        if (file_exists(
            $_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.jpg'
        )) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.jpg';
        }
        if (file_exists(
            $_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.gif'
        )) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.gif';
        }

        return $_configuration['root_web'].'/main/img/icons/'.$size.'/'.$pixname.'.png';
    }
}
