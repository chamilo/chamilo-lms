<?php

namespace ChamiloLMS\Component\Editor\Toolbar;

class Basic
{
    public $config;

    public function __construct($toolbar = null)
    {
        if (!empty($toolbar)) {
            if (class_exists(__NAMESPACE__."\\".$toolbar)) {
                $class = __NAMESPACE__."\\".$toolbar;
                $customToolbar = new $class;
                $this->config = $customToolbar->getConfig();
            }
        }
    }

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
            array('name' => 'document',  'groups' =>array('mode', 'document', 'doctools')),
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
            array('name' => 'allMedias')

        );

        // file manager (elfinder)

        // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
        $config['filebrowserBrowseUrl'] = api_get_path(WEB_PUBLIC_PATH).'editor/filemanager';

        /*filebrowserFlashBrowseUrl
        filebrowserFlashUploadUrl
        filebrowserImageBrowseLinkUrl
        filebrowserImageBrowseUrl
        filebrowserImageUploadUrl
        filebrowserUploadUrl*/

        $config['extraPlugins'] = 'oembed,video';


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

