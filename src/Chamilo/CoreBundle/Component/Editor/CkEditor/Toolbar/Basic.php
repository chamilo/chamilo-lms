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
    public $defaultPlugins = array(
        'oembed',
        'video',
        'wordcount',
        'templates',
        'justify'
    );

    public $plugins = array();

    /**
     * @return array
     */
    public function getConfig()
    {
        // Original from ckeditor
        /*
        $config['toolbarGroups'] = array(
            array('name' => 'document',  'groups' =>array('mode', 'document', 'doctools')),
            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo', )),
            array('name' => 'editing',    'groups' =>array('clipboard', 'undo', )),
            array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
            '/',
            array('name' => 'basicstyles',    'groups' =>array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align' )),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'about')
        );*/

        $config['toolbarGroups'] = array(
                 //{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
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
