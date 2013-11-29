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
class BootstrapLabelExtension extends Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            'label'          => new Twig_Filter_Method($this, 'labelFilter', array('is_safe' => array('html'))),
            'label_success'  => new Twig_Filter_Method($this, 'labelSuccessFilter', array('is_safe' => array('html'))),
            'label_warning'  => new Twig_Filter_Method($this, 'labelWarningFilter', array('is_safe' => array('html'))),
            'label_important'=> new Twig_Filter_Method($this, 'labelImportantFilter', array('is_safe' => array('html'))),
            'label_info'     => new Twig_Filter_Method($this, 'labelInfoFilter', array('is_safe' => array('html'))),
            'label_inverse'  => new Twig_Filter_Method($this, 'labelInverseFilter', array('is_safe' => array('html')))
        );
    }

    /**
     * Returns the HTML code for a label.
     *
     * @param string $text The text of the label
     * @param string $type The type of label
     *
     * @return string The HTML code of the label
     */
    public function labelFilter($text, $type = null)
    {
        return sprintf('<span class="label%s">%s</span>', ($type ? ' label-' . $type : ''), $text);
    }

    /**
     * Returns the HTML code for a success label.
     *
     * @param string $text The text of the label
     *
     * @return string The HTML code of the label
     */
    public function labelSuccessFilter($text)
    {
        return $this->labelFilter($text, 'success');
    }

    /**
     * Returns the HTML code for a warning label.
     *
     * @param string $text The text of the label
     *
     * @return string The HTML code of the label
     */
    public function labelWarningFilter($text)
    {
        return $this->labelFilter($text, 'warning');
    }

    /**
     * Returns the HTML code for a important label.
     *
     * @param string $text The text of the label
     *
     * @return string The HTML code of the label
     */
    public function labelImportantFilter($text)
    {
        return $this->labelFilter($text, 'important');
    }

    /**
     * Returns the HTML code for a info label.
     *
     * @param string $text The text of the label
     *
     * @return string The HTML code of the label
     */
    public function labelInfoFilter($text)
    {
        return $this->labelFilter($text, 'info');
    }

    /**
     * Returns the HTML code for a inverse label.
     *
     * @param string $text The text of the label
     *
     * @return string The HTML code of the label
     */
    public function labelInverseFilter($text)
    {
        return $this->labelFilter($text, 'inverse');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'bootstrap_label_extension';
    }
}
