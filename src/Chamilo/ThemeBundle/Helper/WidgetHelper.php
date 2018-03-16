<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Helper;

/**
 * Class WidgetHelper.
 *
 * @package Chamilo\ThemeBundle\Helper
 */
class WidgetHelper extends \Twig_Extension
{
    /**
     * Get widget helper name.
     *
     * @return string
     */
    public function getName()
    {
        return 'widget';
    }
}
