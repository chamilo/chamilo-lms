<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\CkEditor\Toolbar;

use ChamiloLMS\Component\Editor\Toolbar;

/**
 * Class Basic
 * @package ChamiloLMS\Component\Editor\CkEditor\Toolbar
 */
class Basic extends Toolbar
{
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
            array('name' => 'document',  'groups' =>array('document', 'doctools')),
            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo', )),
            array('name' => 'editing',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
            '/',
            array('name' => 'basicstyles',    'groups' =>array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align')),
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
        $config['filebrowserBrowseUrl'] = api_get_path(WEB_PUBLIC_PATH).'editor/filemanager';
        $config['templates_files'] = array(api_get_path(WEB_PUBLIC_PATH).'editor/templates');

        /*filebrowserFlashBrowseUrl
        filebrowserFlashUploadUrl
        filebrowserImageBrowseLinkUrl
        filebrowserImageBrowseUrl
        filebrowserImageUploadUrl
        filebrowserUploadUrl*/

        //$config['extraPlugins'] = 'oembed,video,wordcount';
        $config['extraPlugins'] = 'oembed,video';
        //$config['oembed_maxWidth'] = '560';
        //$config['oembed_maxHeight'] = '315';

        $config['allowedContent'] = true;

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
