<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Twig;

use Twig_Environment;

/**
 * Class WidgetExtension.
 *
 * @package Chamilo\ThemeBundle\Twig
 */
class WidgetExtension extends \Twig_Extension
{
    /**
     * @var Twig_Environment
     */
    protected $env;

    public function renderWidget()
    {
    }

    public function getFunctions()
    {
        return [
            'widget_box' => new \Twig_SimpleFunction(
                'widget_box',
                [$this, 'renderWidget'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function initRuntime(Twig_Environment $environment)
    {
        $this->env = $environment;
    }

    public function getName()
    {
        return 'chamilo_widget';
    }
}
