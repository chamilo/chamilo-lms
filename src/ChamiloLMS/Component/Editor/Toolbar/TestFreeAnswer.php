<?php

namespace ChamiloLMS\Component\Editor\Toolbar;

class TestFreeAnswer extends Basic
{

    public function getConfig()
    {
        $config['toolbarGroups'] = array(
//            array('name' => 'document',  'groups' =>array('mode', 'document', 'doctools')),
//            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'editing',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
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
            array('name' => 'mode') // source
        );

        $config['fullPage'] = false;
        //$config['height'] = '200';

        return $config;
    }
}


