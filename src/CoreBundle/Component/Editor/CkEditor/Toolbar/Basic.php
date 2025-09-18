<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

class Basic extends Toolbar
{
    /**
     * Plugins for this toolbar (legacy-specific additions).
     * We only add conditional extras here; the base set comes from tiny-settings.js.
     */
    public array $plugins = [];
    private string $toolbarSet;

    public function __construct(
        $router,
        $toolbar = null,
        $config = [],
        $prefix = null
    ) {
        $isAllowedToEdit = api_is_allowed_to_edit();
        $isPlatformAdmin = api_is_platform_admin();

        // Conditional TinyMCE plugin additions (names must match TinyMCE plugin dirs or external plugins)
        $plugins = [];

        if ('ismanual' === api_get_setting('show_glossary_in_documents')) {
            $plugins[] = 'glossary'; // ensure you provide external_plugins mapping if custom
        }

        if ('true' === api_get_setting('youtube_for_students')) {
            $plugins[] = 'youtube';
        } else {
            if ($isAllowedToEdit || $isPlatformAdmin) {
                $plugins[] = 'youtube';
            }
        }

        if ('true' === api_get_setting('enabled_googlemaps')) {
            $plugins[] = 'leaflet';
        }

        if ('true' === api_get_setting('math_asciimathML')) {
            $plugins[] = 'asciimath';
        }

        if ('true' === api_get_setting('enabled_mathjax')) {
            $plugins[] = 'mathjax';
            $config['mathJaxLib'] = api_get_path(WEB_PUBLIC_PATH).'assets/MathJax/MathJax.js?config=TeX-MML-AM_HTMLorMML';
        }

        if ('true' === api_get_setting('enabled_asciisvg')) {
            $plugins[] = 'asciisvg';
        }

        if ('true' === api_get_setting('enabled_wiris')) {
            // Commercial plugin (external)
            $plugins[] = 'ckeditor_wiris';
        }

        if ('true' === api_get_setting('enabled_imgmap')) {
            $plugins[] = 'mapping';
        }

        if ('true' === api_get_setting('more_buttons_maximized_mode')) {
            $plugins[] = 'toolbarswitch';
        }

        if ('true' === api_get_setting('allow_spellcheck')) {
            $plugins[] = 'scayt';
        }

        if (api_get_configuration_sub_value('ckeditor_vimeo_embed/config') && ($isAllowedToEdit || $isPlatformAdmin)) {
            $plugins[] = 'ckeditor_vimeo_embed';
        }

        if ('true' === api_get_setting('editor.ck_editor_block_image_copy_paste')) {
            $plugins[] = 'blockimagepaste';
        }

        // Save only conditional plugins; the base comes from tiny-settings.js
        $this->plugins = array_values(array_unique($plugins));
        $this->toolbarSet = $toolbar;
        parent::__construct($router, $toolbar, $config, $prefix);
    }

    /**
     * Get the toolbar config.
     *
     * We do NOT set the base plugin list or the full toolbar here.
     * We only:
     *  - add conditional plugins (will be UNIONed with the shared base by buildTinyMceConfig)
     *  - add external_plugins paths for custom plugins
     *  - set editor behavior flags (skin, content_css, etc.)
     *  - set language (language, language_url)
     */
    public function getConfig()
    {
        $config = [];

        // Optional external custom plugins mapping (only URLs, activation via JS policy)
        $customPluginsMap = [];
        if ('true' === api_get_setting('editor.translate_html')) {
            $this->plugins[] = 'translatehtml';
            $customPluginsMap['translatehtml'] =
                api_get_path(WEB_PUBLIC_PATH).'libs/editor/tinymce_plugins/translatehtml/plugin.js';
        }

        // If you want JS to be the single source of truth for plugins:
        //   DO NOT set $config['plugins'] here.
        // If you still want conditional extras from PHP (and PLUGINS_POLICY='union'):
        if (!empty($this->plugins)) {
            $config['plugins'] = implode(' ', $this->plugins);
        }

        if (!empty($customPluginsMap)) {
            $config['external_plugins'] = $customPluginsMap;
        }

        $config['skin'] = false;
        $config['content_css'] = false;
        $config['branding'] = false;
        $config['relative_urls'] = false;
        $config['toolbar_mode'] = 'sliding';
        $config['autosave_ask_before_unload'] = true;

        $config['image_title'] = true;
        $config['automatic_uploads'] = true;
        $config['file_picker_types'] = 'file image media';
        $config['file_picker_callback'] = '[browser]';

        // Language
        $iso = api_get_language_isocode();
        $languageConfig = $this->getLanguageConfig($iso);
        $config = array_merge($config, $languageConfig);

        $config['height'] = '300';

        // DO NOT set $config['toolbar'] (toolbar comes from tiny-settings.js)
        // If you *must* add a single extra button from PHP, you could:
        // $config['toolbar'] = 'translatehtml'; // builder will handle concat/dedupe per policy

        $this->config = $config;
        return $this->config;
    }

    /**
     * When minimized or maximized toolbars are requested by legacy code,
     * we keep returning null/arrays but avoid defining the full TinyMCE toolbar.
     * The shared base toolbar from tiny-settings.js will still apply.
     */
    protected function getNormalToolbar()
    {
        return null;
    }

    protected function getMinimizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            ['Undo', 'Redo'],
            // NOTE: left intentionally as a legacy stub; TinyMCE toolbar comes from shared base.
        ];
    }

    protected function getMaximizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            // NOTE: legacy visualization only; shared base controls TinyMCE toolbar.
        ];
    }

    public function getNewPageBlock()
    {
        return ['NewPage', 'Templates', '-', 'PasteFromWord', 'inserthtml'];
    }

    /**
     * Determines the appropriate language configuration for the editor.
     */
    private function getLanguageConfig(string $iso): array
    {
        $url = api_get_path(WEB_PATH);
        $sysUrl = api_get_path(SYS_PATH);
        $defaultLang = 'en';
        $defaultLangFile = "libs/editor/langs/{$defaultLang}.js";
        $specificLangFile = "libs/editor/langs/{$iso}.js";
        $generalLangFile = null;

        // Default configuration set to English
        $config = [
            'language' => $defaultLang,
            'language_url' => $defaultLangFile,
        ];

        if ('en_US' !== $iso) {
            if (str_contains($iso, '_')) {
                // Extract general language code (e.g., "de" from "de_DE")
                list($generalLangCode) = explode('_', $iso, 2);
                $generalLangFile = "libs/editor/langs/{$generalLangCode}.js";
            }

            if (file_exists($sysUrl.$specificLangFile)) {
                $config['language'] = $iso;
                $config['language_url'] = $url.$specificLangFile;
            } elseif (null !== $generalLangFile && file_exists($sysUrl.$generalLangFile)) {
                $config['language'] = $generalLangCode;
                $config['language_url'] = $url.$generalLangFile;
            }
        }

        return $config;
    }
}
