<?php
/**
 * This file is part of BcBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Bc\Bundle\BootstrapBundle\Twig;

use Twig_Extension;
use Twig_Filter_Method;

/**
 * BootstrapIconExtension
 *
 * @category   TwigExtension
 * @package    BcBootstrapBundle
 * @subpackage Twig
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class BootstrapIconExtension extends Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            'parse_icons'   => new Twig_Filter_Method($this, 'parseIconsFilter', array('is_safe' => array('html'))),
            'icon'          => new Twig_Filter_Method($this, 'iconFilter', array('is_safe' => array('html')))
        );
    }

    /**
     * Parses the given string and replaces all occurrences of .icon-[name] with the corresponding icon.
     *
     * @param string $text  The text to parse
     * @param string $color The color of the icon; can be "black" or "white"; defaults to "black"
     *
     * @return string The HTML code with the icons
     */
    public function parseIconsFilter($text, $color = 'black')
    {
        $that = $this;

        return preg_replace_callback(
            '/\.icon-([a-z0-9-]+)/',
            function ($matches) use ($color, $that) {
                return $that->iconFilter($matches[1], $color);
            },
            $text
        );
    }

    /**
     * Returns the HTML code for the given icon.
     *
     * @param string $icon  The name of the icon
     * @param string $color The color of the icon; can be "black" or "white"; defaults to "black"
     *
     * @return string The HTML code for the icon
     */
    public function iconFilter($icon, $color = 'black')
    {
        return sprintf('<i class="%sicon-%s"></i>', $color == 'white' ? 'icon-white ' : '', $icon);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'bootstrap_icon_extension';
    }
}
