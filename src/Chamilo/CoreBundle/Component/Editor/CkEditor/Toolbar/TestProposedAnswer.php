<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Class TestProposedAnswer
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class TestProposedAnswer
{

    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            //array('name' => 'document'),
            array('name' => 'clipboard',    'groups' =>array('clipboard', 'undo', )),
            array('name' => 'basicstyles',    'groups' =>array('basicstyles', 'cleanup', )),
            array('name' => 'paragraph',    'groups' =>array('list', 'indent', 'blocks', 'align' )),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'mode')
        );

        $config['toolbarCanCollapse'] = true;
        $config['toolbarStartupExpanded'] = false;
        //$config['width'] = '100';
        //$config['height'] = '200';
        return $config;
    }
}
