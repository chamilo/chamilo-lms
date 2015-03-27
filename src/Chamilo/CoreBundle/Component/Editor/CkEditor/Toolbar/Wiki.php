<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Wiki toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class Wiki extends Basic
{

    public function getConfig()
    {
        $config['toolbar_minToolbar'] = [
            ['Save', 'NewPage', 'Templates', '-', 'PasteText'],
            ['Undo', 'Redo'],
            ['Wikilink', 'Link', 'Image', 'Video', 'Flash', 'Audio', 'Table',  'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
            ['Format', 'Font', 'FontSize', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch']
        ];

        $config['forcePasteAsPlainText'] = false;

        if (api_get_setting('force_wiki_paste_as_plain_text') == 'true') {
            $config['forcePasteAsPlainText'] = true;
        }

        return $config;
    }

}
