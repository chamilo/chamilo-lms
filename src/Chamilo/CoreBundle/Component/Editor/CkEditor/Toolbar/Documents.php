<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Documents toolbar configuration
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class Documents extends Basic
{

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $config['toolbar_minToolbar'] = [
            ['Save', 'NewPage', 'Templates', '-', 'PasteFromWord'],
            ['Undo', 'Redo'],
            ['Link', 'Image', 'Video', 'Flash', 'Youtube', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyBlock'],
            ['Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch', 'ShowBlocks']
        ];

        $config['extraPlugins'] = $this->getPluginsToString();
        //$config['mathJaxLib'] = $this->urlGenerator->generate('javascript').'/math_jax/MathJax.js?config=default';
        //$config['mathJaxLib'] = api_get_path(WEB_LIBRARY_JS_PATH).'/math_jax/MathJax.js?config=default';
        $config['fullPage'] = true;

        return $config;
    }

    /**
     * @return array
     */
    public function getConditionalPlugins()
    {
        $plugins = array();

        if (api_get_setting('show_glossary_in_documents') != 'none') {
            $plugins[] = 'glossary';
        }
        return $plugins;
    }

}
