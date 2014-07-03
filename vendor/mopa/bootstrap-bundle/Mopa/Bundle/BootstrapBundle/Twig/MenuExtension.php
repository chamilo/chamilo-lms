<?php

namespace Mopa\Bundle\BootstrapBundle\Twig;

use Mopa\Bundle\BootstrapBundle\Menu\Converter\MenuConverter;
use Knp\Menu\Twig\Helper;

/**
 * Extension for rendering a bootstrap menu
 *
 * This function provides some more features than knp_menu_render but does more or less the same
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class MenuExtension extends \Twig_Extension
{
    protected $helper;
    protected $menuTemplate;

    /**
     * @param \Knp\Menu\Twig\Helper $helper
     * @param string                $menuTemplate
     */
    public function __construct(Helper $helper, $menuTemplate)
    {
        $this->helper = $helper;
        $this->menuTemplate = $menuTemplate;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'mopa_bootstrap_menu' => new \Twig_Function_Method($this, 'renderMenu', array('is_safe' => array('html'))),
        );
    }

    /**
     * Renders the Menu with the specified renderer.
     *
     * @param \Knp\Menu\ItemInterface|string|array $menu
     * @param array                                $options
     * @param string                               $renderer
     *
     * @return string
     */
    public function renderMenu($menu, array $options = array(), $renderer = null)
    {
        $options = array_merge(array(
            'template' => $this->menuTemplate,
            'currentClass' => 'active',
            'ancestorClass' => 'active',
            'allow_safe_labels' => true,
        ), $options);

        if (!$menu instanceof ItemInterface) {
            $path = array();
            if (is_array($menu)) {
                if (empty($menu)) {
                    throw new \InvalidArgumentException('The array cannot be empty');
                }
                $path = $menu;
                $menu = array_shift($path);
            }

            $menu = $this->helper->get($menu, $path);
        }

        $menu = $this->helper->get($menu, array(), $options);

        if (isset($options['automenu'])) {
            $converter = new MenuConverter();
            $converter->convert($menu, $options);
        }

        return $this->helper->render($menu, $options, $renderer);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mopa_bootstrap_menu';
    }
}
