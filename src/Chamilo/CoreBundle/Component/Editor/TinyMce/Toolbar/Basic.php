<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\TinyMce\Toolbar;

use Chamilo\CoreBundle\Component\Editor\Toolbar;

/**
 * Class Basic.
 *
 * @package Chamilo\CoreBundle\Component\Editor\TinyMce\Toolbar\Basic
 */
class Basic extends Toolbar
{
    public function getConfig()
    {
        $config = [
            'theme' => "modern",
            'width' => 300,
            'height' => 300,
            'plugins' => [
                "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                "save table contextmenu directionality emoticons template paste textcolor",
            ],
            'content_css' => "css/content.css",
            'toolbar' => "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons",
            'file_browser_callback' => 'elFinderBrowser',
        ];

        if (isset($this->config)) {
            $this->config = array_merge($config, $this->config);
        } else {
            $this->config = $config;
        }

        return $this->config;
    }
}
