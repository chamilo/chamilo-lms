<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Class TestQuestionDescription
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class TestQuestionDescription extends Basic
{
    /**
     * @return mixed
     */
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            array('name' => 'document',  'groups' =>array('document', 'doctools')),
            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo')),
            array('name' => 'editing',    'groups' =>array('clipboard', 'undo')),
            //array('name' => 'forms',    'groups' =>array('clipboard', 'undo', )),
            '/',
            array('name' => 'basicstyles',    'groups' =>array('basicstyles', 'cleanup')),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align')),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'mode')
        );

        $config['extraPlugins'] = $this->getPluginsToString();

        //$config['width'] = '100';
        //$config['height'] = '200';
        return $config;
    }

    /**
     * @return array
     */
    public function getConditionalPlugins()
    {
        $plugins = array();
        if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
            $plugins[] = 'glossary';
        }

        return $plugins;
    }

}
