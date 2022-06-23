<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

class Basic extends Toolbar
{
    /**
     * Default plugins that will be use in all toolbars
     * In order to add a new plugin you have to load it in default/layout/head.tpl.
     */
    public array $defaultPlugins = [
        //'adobeair',
        //'ajax',
        'audio',
        'image2_chamilo',
        'bidi',
        'colorbutton',
        'colordialog',
        'dialogui',
        'dialogadvtab',
        'div',
        //if you activate this plugin the html, head tags will not be saved
        //'divarea',
        //'docprops',
        'find',
        'flash',
        'font',
        'iframe',
        //'iframedialog',
        'indentblock',
        'justify',
        'language',
        'lineutils',
        'liststyle',
        'newpage',
        'oembed',
        'pagebreak',
        'preview',
        'print',
        'save',
        'selectall',
        //'sharedspace',
        'showblocks',
        'smiley',
        //'sourcedialog',
        //'stylesheetparser',
        //'tableresize',
        'templates',
        //'uicolor',
        'video',
        'widget',
        'wikilink',
        'wordcount',
        'inserthtml',
        //'xml',
        'qmarkersrolls',
    ];

    /**
     * Plugins this toolbar.
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
        // Adding plugins depending of platform conditions
        $plugins = [];

        if ('ismanual' === api_get_setting('show_glossary_in_documents')) {
            $plugins[] = 'glossary';
        }

        if ('true' === api_get_setting('youtube_for_students')) {
            $plugins[] = 'youtube';
        } else {
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
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
            // Commercial plugin
            $plugins[] = 'ckeditor_wiris';
        }

        if ('true' === api_get_setting('enabled_imgmap')) {
            $plugins[] = 'mapping';
        }

        /*if (api_get_setting('block_copy_paste_for_students') == 'true') {
            // Missing
        }*/

        if ('true' === api_get_setting('more_buttons_maximized_mode')) {
            $plugins[] = 'toolbarswitch';
        }

        if ('true' === api_get_setting('allow_spellcheck')) {
            $plugins[] = 'scayt';
        }

        if (api_get_configuration_sub_value('ckeditor_vimeo_embed/config') && ($isAllowedToEdit || $isPlatformAdmin)) {
            $plugins[] = 'ckeditor_vimeo_embed';
        }

        if (api_get_configuration_value('ck_editor_block_image_copy_paste')) {
            $plugins[] = 'blockimagepaste';
        }
        $this->defaultPlugins = array_unique(array_merge($this->defaultPlugins, $plugins));
        $this->toolbarSet = $toolbar;
        parent::__construct($router, $toolbar, $config, $prefix);
    }

    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        $customPlugins = '';
        $customPluginsPath = [];
        if ('true' === api_get_setting('editor.translate_html')) {
            $customPlugins .= ' translatehtml';
            $customPluginsPath['translatehtml'] = api_get_path(WEB_PUBLIC_PATH).'libs/editor/tinymce_plugins/translatehtml/plugin.js';
        }

        $plugins = [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste wordcount '.$customPlugins,
        ];

        if ($this->getConfigAttribute('fullPage')) {
            $plugins[] = 'fullpage';
        }

        $config['plugins'] = implode(' ', $plugins);
        $config['toolbar'] = 'undo redo directionality | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl | '.$customPlugins;

        if (!empty($customPluginsPath)) {
            $config['external_plugins'] = $customPluginsPath;
        }

        $config['skin_url'] = '/build/libs/tinymce/skins/ui/oxide';
        $config['content_css'] = '/build/libs/tinymce/skins/content/default/content.css';
        $config['branding'] = false;
        $config['relative_urls'] = false;
        $config['toolbar_mode'] = 'sliding';
        $config['autosave_ask_before_unload'] = true;
        $config['toolbar_mode'] = 'sliding';

        // enable title field in the Image dialog
        $config['image_title'] = true;
        // enable automatic uploads of images represented by blob or data URIs
        $config['automatic_uploads'] = true;
        // custom filepicker only to Image dialog
        $config['file_picker_types'] = 'image';

        $config['file_picker_callback'] = '[browser]';

        $iso = api_get_language_isocode();
        $url = api_get_path(WEB_PATH);

        // Language list: https://www.tiny.cloud/get-tiny/language-packages/
        if ('en_US' !== $iso) {
            $config['language'] = $iso;
            $config['language_url'] = "$url/libs/editor/langs/$iso.js";
        }

        /*if (isset($this->config)) {
            $this->config = array_merge($config, $this->config);
        } else {
            $this->config = $config;
        }*/

        $this->config = $config;

        //$config['width'] = '100';
        $this->config['height'] = '300';

        return $this->config;
    }

    /**
     * @return array
     */
    public function getNewPageBlock()
    {
        return ['NewPage', 'Templates', '-', 'PasteFromWord', 'inserthtml'];
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     *
     * @return array
     */
    protected function getNormalToolbar()
    {
        return null;
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized.
     *
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            ['Undo', 'Redo'],
            [
                'Link',
                'Image',
                'Video',
                'Oembed',
                'Flash',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'Table',
                'Asciimath',
                'Asciisvg',
            ],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Styles', 'Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'],
            'true' === api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is maximized.
     *
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return [
            $this->getNewPageBlock(),
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'inserthtml'],
            ['Undo', 'Redo', '-', 'SelectAll', 'Find', '-', 'RemoveFormat'],
            ['Link', 'Unlink', 'Anchor', 'Glossary'],
            [
                'Image',
                'Mapping',
                'Video',
                'Oembed',
                'Flash',
                'Youtube',
                'VimeoEmbed',
                'Audio',
                'leaflet',
                'Smiley',
                'SpecialChar',
                'Asciimath',
                'Asciisvg',
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'],
            ['true' === api_get_setting('allow_spellcheck') ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks'],
            'true' === api_get_setting('enabled_wiris') ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }
}
