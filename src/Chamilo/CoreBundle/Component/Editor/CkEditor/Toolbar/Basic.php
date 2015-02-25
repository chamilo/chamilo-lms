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
        'oembed',
        'video',
        'audio',
        'wordcount',
        'templates',
        'justify',
        'colorbutton',
        'flash',
        'link',
        'table',
        'wikilink'
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

        if (api_get_setting('youtube_for_students') == 'true') {
            $plugins[] = 'youtube';
        } else {
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
                $plugins[] = 'youtube';
            }
        }

        if (api_get_setting('enabled_googlemaps') == 'true') {
            $plugins[] = 'leaflet';
            $plugins[] = 'mapping';
        }

        if (api_get_setting('math_asciimathML') == 'true') {
            $plugins[] = 'asciimath';
        }
        $plugins[] = 'asciimath';

        if (api_get_setting('enabled_asciisvg') == 'true') {
            $plugins[] = 'asciisvg';
        }

        if (api_get_setting('enabled_wiris') == 'true') {
            // Commercial plugin
            //$plugins[] = 'ckeditor_wiris';
        }

        if (api_get_setting('enabled_imgmap') == 'true') {
            // Commercial plugin
        }

        if (api_get_setting('block_copy_paste_for_students') == 'true') {
            // Missing
        }

        $this->defaultPlugins = array_merge($this->defaultPlugins, $plugins);
        parent::__construct($toolbar, $config, $prefix);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        // Original from ckeditor
        $config['toolbarGroups'] = array(
            array('name' => 'document',   'groups' =>array('mode', 'document', 'doctools')),
            array('name' => 'clipboard',  'groups' =>array('clipboard', 'undo', )),
            array('name' => 'editing',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
            '/',
            array('name' => 'basicstyles', 'groups' =>array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',   'groups' =>array('list', 'indent', 'blocks', 'align')),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'allMedias'),
            array('name' => 'mode')
        );

        // file manager (elfinder)

        // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
        $config['filebrowserBrowseUrl'] = api_get_path(WEB_LIBRARY_PATH).'elfinder/filemanager.php';

        $config['customConfig'] = api_get_path(WEB_LIBRARY_PATH).'javascript/ckeditor/config_js.php';

        /*filebrowserFlashBrowseUrl
        filebrowserFlashUploadUrl
        filebrowserImageBrowseLinkUrl
        filebrowserImageBrowseUrl
        filebrowserImageUploadUrl
        filebrowserUploadUrl*/

        $config['extraPlugins'] = $this->getPluginsToString();

        //$config['oembed_maxWidth'] = '560';
        //$config['oembed_maxHeight'] = '315';

        //$config['allowedContent'] = true;

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

        //$config['skins'] = 'moono';

        if (isset($this->config)) {
            $this->config = array_merge($config, $this->config);
        } else {
            $this->config = $config;
        }

        //$config['width'] = '100';
        //$config['height'] = '200';
        return $this->config;
    }
}
