<?php

namespace ChamiloLMS\Component\Editor\Toolbar;

class TestProposedAnswer
{

    public function getConfig()
    {

        $config['toolbarGroups'] = array(
            array('name' => 'document',  'groups' => array('mode')),
            array('name' => 'basicstyles',    'groups' => array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',    'groups' => array('list', 'indent', 'blocks', 'align' )),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors')
        );

        $config['toolbarCanCollapse'] = true;
        $config['toolbarStartupExpanded'] = false;
        //$config['width'] = '100';
        //$config['height'] = '200';
        return $config;
    }
}

