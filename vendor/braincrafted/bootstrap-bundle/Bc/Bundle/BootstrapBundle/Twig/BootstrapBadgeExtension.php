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
 * BootstrapLabelExtension
 *
 * @category   TwigExtension
 * @package    BcBootstrapBundle
 * @subpackage Twig
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class BootstrapBadgeExtension extends Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            'badge'          => new Twig_Filter_Method($this, 'badgeFilter', array('is_safe' => array('html'))),
            'badge_success'  => new Twig_Filter_Method($this, 'badgeSuccessFilter', array('is_safe' => array('html'))),
            'badge_warning'  => new Twig_Filter_Method($this, 'badgeWarningFilter', array('is_safe' => array('html'))),
            'badge_important'=> new Twig_Filter_Method($this, 'badgeImportantFilter', array('is_safe' => array('html'))),
            'badge_info'     => new Twig_Filter_Method($this, 'badgeInfoFilter', array('is_safe' => array('html'))),
            'badge_inverse'  => new Twig_Filter_Method($this, 'badgeInverseFilter', array('is_safe' => array('html')))
        );
    }

    /**
     * Returns the HTML code for a badge.
     *
     * @param string $text The text of the badge
     * @param string $type The type of badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeFilter($text, $type = null)
    {
        return sprintf('<span class="badge%s">%s</span>', ($type ? ' badge-' . $type : ''), $text);
    }

    /**
     * Returns the HTML code for a success badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeSuccessFilter($text)
    {
        return $this->badgeFilter($text, 'success');
    }

    /**
     * Returns the HTML code for a warning badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeWarningFilter($text)
    {
        return $this->badgeFilter($text, 'warning');
    }

    /**
     * Returns the HTML code for a important badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeImportantFilter($text)
    {
        return $this->badgeFilter($text, 'important');
    }

    /**
     * Returns the HTML code for a info badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeInfoFilter($text)
    {
        return $this->badgeFilter($text, 'info');
    }

    /**
     * Returns the HTML code for a inverse badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeInverseFilter($text)
    {
        return $this->badgeFilter($text, 'inverse');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'bootstrap_badge_extension';
    }
}
