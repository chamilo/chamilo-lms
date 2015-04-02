<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * Glossary toolbar configuration
 * 
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar
 */
class Glossary extends Basic
{

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $config['toolbar_minToolbar'] = [
            ['Save', 'NewPage', 'Templates', 'NewPage', '-', 'PasteFromWord'],
            ['Undo', 'Redo'],
            ['Link', 'Image', 'Video', 'Flash', 'Audio', 'Table', 'Asciimath', 'Asciisvg'],
            ['BulletedList', 'NumberedList', 'HorizontalRule'],
            ['JustifyLeft', 'JustifyCenter', 'JustifyBlock'],
            ['Format', 'Font', 'Bold', 'Italic', 'Underline', 'TextColor', 'BGColor', 'Source'],
            ['Toolbarswitch']
        ];

        return $config;
    }

}
