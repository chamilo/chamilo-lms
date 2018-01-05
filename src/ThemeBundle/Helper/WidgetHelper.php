<?php
/**
 * WidgetHelper.php
 * avanzu-admin
 * Date: 16.03.14
 */

namespace Chamilo\ThemeBundle\Helper;


class WidgetHelper extends \Twig_Extension {

    /**
     * Get widget helper name
     * @return string
     */
    public function getName()
    {
        return 'widget';
    }
}
