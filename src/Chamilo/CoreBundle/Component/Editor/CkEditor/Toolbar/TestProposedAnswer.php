<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TestProposedAnswer toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class TestProposedAnswer extends Basic
{
    /**
     * Get the toolbar config
     * @return array
     */
    public function getConfig()
    {
        $config['toolbarGroups'] = array(
            array('name' => 'document'),
            array(
                'name' => 'clipboard',
                'groups' => array('clipboard', 'undo',)
            ),
            array(
                'name' => 'basicstyles',
                'groups' => array('basicstyles', 'cleanup',)
            ),
            array(
                'name' => 'paragraph',
                'groups' => array('list', 'indent', 'blocks', 'align')
            ),
            array('name' => 'links'),
            array('name' => 'insert'),
            '/',
            array('name' => 'styles'),
            array('name' => 'colors'),
            array('name' => 'mode'),
            array('name' => 'others')
        );

        $config['toolbarCanCollapse'] = true;
        $config['toolbarStartupExpanded'] = false;
        $config['extraPlugins'] = $this->getPluginsToString();
        //$config['width'] = '100';
        //$config['height'] = '200';
        if (api_get_setting('more_buttons_maximized_mode') != 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();

            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }
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

    /**
     * Get the toolbar configuration when CKEditor is maximized
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return [
            ['NewPage', 'Templates', '-', 'Preview', 'Print'],
            ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
            ['Undo', 'Redo', '-', 'SelectAll', 'Find', '-', 'RemoveFormat'],
            ['Link', 'Unlink', 'Anchor', 'Glossary'],
            [
                'Image',
                'Mapping',
                'Video',
                'Flash',
                'Youtube',
                'Oembed',
                'Audio',
                'leaflet',
                'Smiley',
                'SpecialChar',
                'Asciimath',
                'Asciisvg'
            ],
            '/',
            ['Table', '-', 'CreateDiv'],
            ['BulletedList', 'NumberedList', 'HorizontalRule', '-', 'Outdent', 'Indent', 'Blockquote'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'],
            [api_get_setting('allow_spellcheck') == 'true' ? 'Scayt' : ''],
            ['Styles', 'Format', 'Font', 'FontSize'],
            ['PageBreak', 'ShowBlocks', 'Source'],
            ['Toolbarswitch'],
        ];
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            [
                'Maximize',
                'Bold',
                'Image',
                'Link',
                'PasteFromWord',
                'Audio',
                'Table',
                'Subscript',
                'Superscript',
                'Source'
            ]
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return [
            ['Templates'],
            ['PasteFromWord'],
            ['Link'],
            ['Image', 'Video', 'Flash', 'Audio', 'Asciimath', 'Asciisvg'],
            ['Table'],
            ['Bold'],
            ['Source', 'Toolbarswitch']
        ];
    }
}
