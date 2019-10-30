<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

/**
 * Class Basic.
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class Basic extends Toolbar
{
    /**
     * Default plugins that will be use in all toolbars
     * In order to add a new plugin you have to load it in default/layout/head.tpl.
     *
     * @var array
     */
    public $defaultPlugins = [
        'adobeair',
        'ajax',
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
        'docprops',
        'find',
        'flash',
        'font',
        'iframe',
        'iframedialog',
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
        'sharedspace',
        'showblocks',
        'smiley',
        'sourcedialog',
        'stylesheetparser',
        'tableresize',
        'templates',
        'uicolor',
        'video',
        'widget',
        'wikilink',
        'wordcount',
        'inserthtml',
        'xml',
        'qmarkersrolls',
    ];

    /**
     * Plugins this toolbar.
     *
     * @var array
     */
    public $plugins = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $router,
        $toolbar = null,
        $config = [],
        $prefix = null
    ) {
        // Adding plugins depending of platform conditions
        $plugins = [];

        if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
            $plugins[] = 'glossary';
        }

        if (api_get_setting('youtube_for_students') == 'true') {
            $plugins[] = 'youtube';
        } else {
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
                $plugins[] = 'youtube';
            }
        }

        if (api_get_setting('enabled_googlemaps') == 'true') {
            $plugins[] = 'leaflet';
        }

        if (api_get_setting('math_asciimathML') == 'true') {
            $plugins[] = 'asciimath';
        }

        if (api_get_setting('enabled_mathjax') == 'true') {
            $plugins[] = 'mathjax';
            $config['mathJaxLib'] = api_get_path(WEB_PUBLIC_PATH).'assets/MathJax/MathJax.js?config=TeX-MML-AM_HTMLorMML';
        }

        if (api_get_setting('enabled_asciisvg') == 'true') {
            $plugins[] = 'asciisvg';
        }

        if (api_get_setting('enabled_wiris') == 'true') {
            // Commercial plugin
            $plugins[] = 'ckeditor_wiris';
        }

        if (api_get_setting('enabled_imgmap') == 'true') {
            $plugins[] = 'mapping';
        }

        /*if (api_get_setting('block_copy_paste_for_students') == 'true') {
            // Missing
        }*/

        if (api_get_setting('more_buttons_maximized_mode') == 'true') {
            $plugins[] = 'toolbarswitch';
        }

        if (api_get_setting('allow_spellcheck') == 'true') {
            $plugins[] = 'scayt';
        }

        $this->defaultPlugins = array_unique(array_merge($this->defaultPlugins, $plugins));
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
        if (api_get_setting('more_buttons_maximized_mode') === 'true') {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        $config['customConfig'] = api_get_path(WEB_PUBLIC_PATH).'editor/config?'.api_get_cidreq();
        $config['flash_flvPlayer'] = api_get_path(WEB_LIBRARY_JS_PATH).'ckeditor/plugins/flash/swf/player.swf';

        /*filebrowserFlashBrowseUrl
        filebrowserFlashUploadUrl
        filebrowserImageBrowseLinkUrl
        filebrowserImageBrowseUrl
        filebrowserImageUploadUrl
        filebrowserUploadUrl*/

        $config['extraPlugins'] = $this->getPluginsToString();

        //$config['oembed_maxWidth'] = '560';
        //$config['oembed_maxHeight'] = '315';

        /*$config['wordcount'] = array(
            // Whether or not you want to show the Word Count
            'showWordCount' => true,
            // Whether or not you want to show the Char Count
            'showCharCount' => true,
            // Option to limit the characters in the Editor
            'charLimit' => 'unlimited',
            // Option to limit the words in the Editor
            'wordLimit' => 'unlimited'
        );*/

        $config['skin'] = 'moono-lisa';

        $config['image2_chamilo_alignClasses'] = [
            'pull-left',
            'text-center',
            'pull-right',
            'img-va-baseline',
            'img-va-top',
            'img-va-bottom',
            'img-va-middle',
            'img-va-super',
            'img-va-sub',
            'img-va-text-top',
            'img-va-text-bottom',
        ];
        $config['startupOutlineBlocks'] = api_get_configuration_value('ckeditor_startup_outline_blocks') === true;

        if (isset($this->config)) {
            $this->config = array_merge($config, $this->config);
        } else {
            $this->config = $config;
        }

        //$config['width'] = '100';
        //$config['height'] = '200';
        return $this->config;
    }

    /**
     * @return array
     */
    public function getNewPageBlock()
    {
        return  ['NewPage', 'Templates', '-', 'PasteFromWord', 'inserthtml'];
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
            ['Link', 'Image', 'Video', 'Oembed', 'Flash', 'Youtube', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Styles', 'Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'],
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
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
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks'],
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch', 'Source'],
        ];
    }
}
