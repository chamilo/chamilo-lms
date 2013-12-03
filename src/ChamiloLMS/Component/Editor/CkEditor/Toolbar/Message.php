<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\CkEditor\Toolbar;

/**
 * Class Message
 * @package ChamiloLMS\Component\Editor\CkEditor\Toolbar
 */
class Message extends Basic
{
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            '/',
            array('name' => 'basicstyles',  'groups' =>array('basicstyles', 'cleanup')),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align')),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others')
        );

        $config['fullPage'] = true;
        //$config['height'] = '200';

        return $config;
    }
}
