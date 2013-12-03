<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\CkEditor\Toolbar;

/**
 * Class TestFreeAnswerStrict
 * @package ChamiloLMS\Component\Editor\CkEditor\Toolbar
 */
class TestFreeAnswerStrict extends Basic
{
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
//            array('name' => 'document',  'groups' =>array('mode', 'document', 'doctools')),
//            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'editing',    'groups' =>array('clipboard', 'undo', )),
            //array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
            /*'/',
            array('name' => 'basicstyles',    'groups' =>array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align' )),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'mode')*/
        );

        $config['fullPage'] = false;

        $config['extraPlugins'] = 'wordcount';

        $config['wordcount'] = array(
            // Whether or not you want to show the Word Count
            'showWordCount' => true,
            // Whether or not you want to show the Char Count
            'showCharCount' => true,
            // Option to limit the characters in the Editor
            'charLimit' => 'unlimited',
            // Option to limit the words in the Editor
            'wordLimit' => 'unlimited'
        );

        $config['removePlugins'] = 'elementspath';
        //$config['height'] = '200';
        return $config;
    }
}
