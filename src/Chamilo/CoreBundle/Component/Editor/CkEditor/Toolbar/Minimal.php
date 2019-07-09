<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;

/**
 * CKEditor Minimal toolbar.
 *
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar *
 */
class Minimal extends Basic
{
    /**
     * @return mixed
     */
    public function getConfig()
    {
        $config['toolbar'] = [
            [
                'name' => 'clipboard',
                'groups' => ['clipboard', 'undo'],
                'items' => ['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo'],
            ],
            [
                'name' => 'basicstyles',
                'groups' => ['basicstyles', 'cleanup'],
                'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'TextColor'],
            ],
            [
                'name' => 'paragraph',
                'groups' => ['list', 'indent', 'blocks', 'align', 'bidi'],
                'items' => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'],
            ],
            [
                'name' => 'links',
                'items' => ['Link', 'Unlink', 'Anchor', 'Source'],
            ],
        ];

        return $config;
    }
}
