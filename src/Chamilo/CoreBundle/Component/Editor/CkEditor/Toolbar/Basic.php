<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

/**
 * Class Basic
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class Basic extends Toolbar
{
    /**
     * Default plugins that will be use in all toolbars
     * In order to add a new plugin you have to load it in default/layout/head.tpl
     * @var array
     */
    public $defaultPlugins = array(
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
    );

    /**
     * Plugins this toolbar
     * @var array
     */
    public $plugins = array();

    /**
     * @inheritdoc
     */
    public function __construct(
        $toolbar = null,
        $config = array(),
        $prefix = null
    ) {
        // Adding plugins depending of platform conditions
        $plugins = array();

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
            $config['mathJaxLib'] = api_get_path(WEB_PATH).'web/assets/MathJax/MathJax.js?config=TeX-AMS_HTML';
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

        $this->defaultPlugins = array_merge($this->defaultPlugins, $plugins);
        parent::__construct($toolbar, $config, $prefix);
    }

    /**
     * Get the toolbar config
     * @return array
     */
    public function getConfig()
    {
        $config = array();
        if (api_get_setting('more_buttons_maximized_mode') === 'true') {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();

            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        $config['customConfig'] = api_get_path(WEB_LIBRARY_JS_PATH).'ckeditor/config_js.php';
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

        $config['skin'] = 'bootstrapck,'.api_get_path(WEB_LIBRARY_JS_PATH).'ckeditor/skins/bootstrapck/';
        //$config['skin'] = 'moono-lisa';

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
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false
     * @return array
     */
    protected function getNormalToolbar()
    {
        return null;
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized
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
            ['Styles', 'Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch']
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is maximized
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
                'Asciisvg'
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'],
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks', 'Source'],
            api_get_setting('enabled_wiris') == 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_CAS'] : [''],
            ['Toolbarswitch'],
        ];
    }

    /**
     * @return array
     */
    public function getNewPageBlock()
    {
        return  ['NewPage', 'Templates', '-', 'PasteFromWord', 'inserthtml'];
    }
}
