<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Component\Editor\CkEditor\Toolbar;

/**
 * Class Documents
 * @package ChamiloLMS\Component\Editor\CkEditor\Toolbar
 */
class Documents extends Basic
{
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            array('name' => 'document',  'groups' => array('document', 'doctools')),
            array('name' => 'clipboard', 'groups' => array('clipboard', 'undo')),
            array('name' => 'editing',   'groups' => array('clipboard', 'undo')),
            //array('name' => 'forms',   'groups' =>array('clipboard', 'undo')),
            '/',
            array('name' => 'basicstyles', 'groups' => array('basicstyles', 'cleanup')),
            array('name' => 'paragraph',   'groups' => array('list', 'indent', 'blocks', 'align')),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'mode')
        );

        $config['extraPlugins'] = $this->getConfigAttribute('extraPlugins').',mathjax';
        $config['mathJaxLib'] = $this->urlGenerator->generate(
            'legacy.controller:getJavascript',
            array('file' => 'math_jax/MathJax.js', 'config'=> 'default')
        );
        $config['fullPage'] = true;
        return $config;
    }
}
