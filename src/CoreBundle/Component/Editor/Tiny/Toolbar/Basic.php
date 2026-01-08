<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Tiny\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

class Basic extends Toolbar
{
    /**
     * Toolbar-specific plugins (legacy additions).
     * Only add conditional extras here; the base set comes from tiny-settings.js.
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

        // Build the candidate list based on platform settings (we will filter by availability later)
        $candidates = [];

        if ('ismanual' === api_get_setting('show_glossary_in_documents')) {
            $candidates[] = 'glossary'; // custom; requires external plugin mapping or core file
        }

        // IMPORTANT: 'youtube' removed to avoid 404; use TinyMCE 'media' for YouTube/Vimeo
        // $candidates[] = 'youtube';

        if ('true' === api_get_setting('enabled_googlemaps')) {
            $candidates[] = 'leaflet';
        }

        if ('true' === api_get_setting('math_asciimathML')) {
            $candidates[] = 'asciimath';
        }

        if ('true' === api_get_setting('enabled_mathjax')) {
            $candidates[] = 'mathjax';
            // MathJax library URL (used by your integration if needed)
            $config['mathJaxLib'] = api_get_path(WEB_PUBLIC_PATH).'assets/MathJax/MathJax.js?config=TeX-MML-AM_HTMLorMML';
        }

        if ('true' === api_get_setting('enabled_asciisvg')) {
            $candidates[] = 'asciisvg';
        }

        if ('true' === api_get_setting('enabled_wiris')) {
            // Commercial/external plugin name used by your integration
            $candidates[] = 'ckeditor_wiris';
        }

        if ('true' === api_get_setting('enabled_imgmap')) {
            $candidates[] = 'mapping';
        }

        if ('true' === api_get_setting('more_buttons_maximized_mode')) {
            $candidates[] = 'toolbarswitch';
        }

        if ('true' === api_get_setting('allow_spellcheck')) {
            $candidates[] = 'scayt';
        }

        if (api_get_configuration_sub_value('ckeditor_vimeo_embed/config') && ($isAllowedToEdit || $isPlatformAdmin)) {
            $candidates[] = 'ckeditor_vimeo_embed';
        }

        if ('true' === api_get_setting('editor.editor_block_image_copy_paste')) {
            $candidates[] = 'blockimagepaste';
        }

        // Optionally add translatehtml if enabled (will be mapped as external if present)
        if ('true' === api_get_setting('editor.translate_html')) {
            $candidates[] = 'translatehtml';
        }

        // Prepare external plugin candidates (name => web URL). Only mapped if file exists.
        $externalCandidates = [
            // Place your custom TinyMCE plugins here if you ship them under /public/libs/editor/tinymce_plugins/<name>/plugin.js
            // 'glossary' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/glossary/plugin.js',
            // 'mapping' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/mapping/plugin.js',
            // 'toolbarswitch' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/toolbarswitch/plugin.js',
            // 'ckeditor_wiris' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/ckeditor_wiris/plugin.js',
            // 'ckeditor_vimeo_embed' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/ckeditor_vimeo_embed/plugin.js',
            // 'scayt' => api_get_path(WEB_PUBLIC_PATH) . 'libs/editor/tinymce_plugins/scayt/plugin.js',
            'translatehtml' => api_get_path(WEB_PUBLIC_PATH).'libs/editor/tinymce_plugins/translatehtml/plugin.js',
        ];

        // Filter candidates by availability (core or external). Build external_plugins map as needed.
        [$availablePlugins, $externalMap] = $this->filterAvailablePlugins($candidates, $externalCandidates);

        $this->plugins = $availablePlugins;
        $this->toolbarSet = $toolbar;

        // Pass through to parent (keeps behavior consistent with existing constructor flow)
        parent::__construct($router, $toolbar, $config, $prefix);

        // Merge any external plugins detected into $this->config later in getConfig()
        // We store them temporarily in a property for use in getConfig()
        $this->detectedExternalPlugins = $externalMap;
    }

    /**
     * @var array<string,string>
     */
    private array $detectedExternalPlugins = [];

    /**
     * Get the toolbar config.
     *
     * Do NOT define the base plugin list or the full toolbar here.
     * Only:
     *  - add conditional plugins (UNIONed with base via buildTinyMceConfig)
     *  - add external_plugins mappings for custom plugins
     *  - set editor behavior flags (skin, content_css, etc.)
     *  - set language (language, language_url)
     */
    public function getConfig()
    {
        $config = [];

        // Provide conditional plugins from PHP; base plugins come from tiny-settings.js
        if (!empty($this->plugins)) {
            $config['plugins'] = implode(' ', $this->plugins);
        }

        // External plugins that were detected as present
        if (!empty($this->detectedExternalPlugins)) {
            $config['external_plugins'] = $this->detectedExternalPlugins;
        }

        $config['skin'] = false;
        $config['content_css'] = false;
        $config['branding'] = false;
        $config['relative_urls'] = false;
        $config['toolbar_mode'] = 'sliding';
        $config['autosave_ask_before_unload'] = true;

        // Uploads / file picking
        $config['image_title'] = true;
        $config['automatic_uploads'] = true;
        $config['file_picker_types'] = 'file image media';
        // Placeholder replaced by the file manager bridge in CkEditor.php
        $config['file_picker_callback'] = '[browser]';

        // Language
        $iso = api_get_language_isocode();
        $languageConfig = $this->getLanguageConfig($iso);
        $config = array_merge($config, $languageConfig);

        $config['height'] = '300';

        // Do NOT set $config['toolbar']; the base toolbar comes from tiny-settings.js
        $this->config = $config;

        return $this->config;
    }

    /**
     * Legacy stubs: we do not define complete toolbars here; tiny-settings.js controls them.
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
            // Legacy stub: the real toolbar is defined in tiny-settings.js
        ];
    }

    protected function getMaximizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            // Legacy stub: the real toolbar is defined in tiny-settings.js
        ];
    }

    public function getNewPageBlock()
    {
        return ['NewPage', 'Templates', '-', 'PasteFromWord', 'inserthtml'];
    }

    /**
     * Determine TinyMCE language configuration (file + code).
     */
    private function getLanguageConfig(string $iso): array
    {
        $url = api_get_path(WEB_PATH);
        $sysUrl = api_get_path(SYS_PATH);
        $defaultLang = 'en';
        $defaultLangFile = "libs/editor/langs/{$defaultLang}.js";
        $specificLangFile = "libs/editor/langs/{$iso}.js";
        $generalLangFile = null;

        // Default to English
        $config = [
            'language' => $defaultLang,
            'language_url' => $defaultLangFile,
        ];

        if ('en_US' !== $iso) {
            if (str_contains($iso, '_')) {
                // Extract general language code (e.g., "de" from "de_DE")
                [$generalLangCode] = explode('_', $iso, 2);
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

    /**
     * Filter a list of plugin names by availability and build an external_plugins map.
     *
     * A plugin is considered available if:
     *  - Core file exists:  {SYS_PUBLIC}/libs/editor/plugins/<name>/plugin.min.js
     *  - OR an external candidate exists: {SYS_PUBLIC}/libs/editor/tinymce_plugins/<name>/plugin.js
     *
     * Returns: [availablePluginNames[], externalPluginsMap[name => webUrl]]
     *
     * @param string[]             $names
     * @param array<string,string> $externalCandidates name => web URL (will be validated on disk)
     *
     * @return array{0: array<int,string>, 1: array<string,string>}
     */
    private function filterAvailablePlugins(array $names, array $externalCandidates): array
    {
        $available = [];
        $externalMap = [];

        $sysPublic = rtrim(api_get_path(SYS_PUBLIC_PATH), '/').'/';
        $webPublic = rtrim(api_get_path(WEB_PUBLIC_PATH), '/').'/';

        foreach (array_unique($names) as $name) {
            $corePath = $sysPublic.'libs/editor/plugins/'.$name.'/plugin.min.js';
            $extPath = $sysPublic.'libs/editor/tinymce_plugins/'.$name.'/plugin.js';
            $extUrl = $externalCandidates[$name] ?? ($webPublic.'libs/editor/tinymce_plugins/'.$name.'/plugin.js');

            if (file_exists($corePath)) {
                // Core plugin exists
                $available[] = $name;

                continue;
            }

            if (file_exists($extPath)) {
                // External plugin exists on disk; map its URL
                $available[] = $name;
                $externalMap[$name] = $extUrl;

                continue;
            }

            // If an explicit external candidate was provided, validate it
            if (isset($externalCandidates[$name])) {
                $explicitPath = $sysPublic.'libs/editor/tinymce_plugins/'.$name.'/plugin.js';
                if (file_exists($explicitPath)) {
                    $available[] = $name;
                    $externalMap[$name] = $externalCandidates[$name];

                    continue;
                }
            }

            // Not available: silently skip (prevents TinyMCE load errors)
        }

        return [$available, $externalMap];
    }
}
