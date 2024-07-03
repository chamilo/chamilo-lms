<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * TestProposedAnswer toolbar configuration.
 */
class TestProposedAnswer extends Basic
{
    /**
     * Get the toolbar config.
     *
     * @return array
     */
    public function getConfig()
    {
        $config['toolbarCanCollapse'] = true;
        $config['toolbarStartupExpanded'] = false;
        if (api_get_setting('more_buttons_maximized_mode') !== 'true') {
            $config['toolbar'] = $this->getNormalToolbar();
        } else {
            $config['toolbar_minToolbar'] = $this->getMinimizedToolbar();
            $config['toolbar_maxToolbar'] = $this->getMaximizedToolbar();
        }

        return $config;
    }

    /**
     * Get the toolbar configuration when CKEditor is maximized.
     *
     * @return array
     */
    protected function getMaximizedToolbar()
    {
        return $this->getNormalToolbar();
    }

    /**
     * Get the default toolbar configuration when the setting more_buttons_maximized_mode is false.
     *
     * @return array
     */
    protected function getNormalToolbar()
    {
        return [
            ['Bold', 'Subscript', 'Superscript'],
            [
                'Link',
                'Image',
                'Video',
                //'Flash',
                'Oembed',
                //'Youtube',
                'VimeoEmbed',
                'Audio',
                'Table',
                'PasteFromWord',
                'inserthtml',
                api_get_setting('enabled_mathjax') === 'true' ? 'Mathjax' : '',
            ],
            ['Asciimath', 'Asciisvg'],
            api_get_setting('enabled_wiris') === 'true' ? ['ckeditor_wiris_formulaEditor', 'ckeditor_wiris_formulaEditorChemistry'] : [''],
            ['Maximize', 'Source'],
        ];
    }

    /**
     * Get the toolbar configuration when CKEditor is minimized.
     *
     * @return array
     */
    protected function getMinimizedToolbar()
    {
        return $this->getNormalToolbar();
    }
}
