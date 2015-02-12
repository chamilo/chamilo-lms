<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Class Documents
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class Documents extends Basic
{
    public $plugins = array(
        'toolbarswitch',
        'audio',
        'video',
        'youtube',
        'leaflet',
        'widget',
        'lineutils',
        'mathjax',
        'asciimath',
        'glossary',
        'asciisvg',
        'mapping'
    );

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            array('name' => 'document',  'groups' => array('document', 'doctools')),
            array('name' => 'clipboard', 'groups' => array('clipboard', 'undo')),
            array('name' => 'editing',   'groups' => array( 'find', 'selection', 'spellchecker')),
            //array('name' => 'forms'),
            '/',
            array('name' => 'basicstyles', 'groups' => array('basicstyles', 'cleanup')),
            array('name' => 'paragraph',   'groups' => array('list', 'indent', 'blocks', 'align', 'bidi')),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            //array('name' => 'tools'),
            array('name' => 'others'),
            array('name' => 'mode')
        );
        $config['extraPlugins'] = $this->getPluginsToString();
        //$config['mathJaxLib'] = $this->urlGenerator->generate('javascript').'/math_jax/MathJax.js?config=default';
        //$config['mathJaxLib'] = api_get_path(WEB_LIBRARY_JS_PATH).'/math_jax/MathJax.js?config=default';
        $config['fullPage'] = true;

        return $config;
    }

}
