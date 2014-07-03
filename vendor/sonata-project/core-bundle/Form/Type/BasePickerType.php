<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\CoreBundle\Form\Type;

use Sonata\CoreBundle\Date\MomentFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


/**
 * Class BasePickerType (to factorize DatePickerType and DateTimePickerType code
 *
 * @package Sonata\CoreBundle\Form\Type
 *
 * @author Hugo Briand <briand@ekino.com>
 */
abstract class BasePickerType extends AbstractType
{
    /**
     * @var MomentFormatConverter
     */
    private $formatConverter;

    /**
     * @param MomentFormatConverter $formatConverter
     */
    public function __construct(MomentFormatConverter $formatConverter)
    {
        $this->formatConverter = $formatConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $format = $this->getDefaultFormat();
        if (isset($options['date_format']) && is_string($options['date_format'])) {
            $format = $options['date_format'];
        } else if (isset($options['format']) && is_string($options['format'])) {
            $format = $options['format'];
        }

        $view->vars['moment_format'] = $this->formatConverter->convert($format);

        $view->vars['type'] = 'text';

        $dpOptions = array();
        foreach ($options as $key => $value) {
            if (false !== strpos($key, "dp_")) {
                // We remove 'dp_' and camelize the options names
                $dpKey = substr($key, 3);
                $dpKey = preg_replace_callback('/_([a-z])/', function ($c) {
                    return strtoupper($c[1]);
                }, $dpKey);

                $dpOptions[$dpKey] = $value;
            }
        }

        $view->vars['dp_options'] = $dpOptions;
    }

    /**
     * Gets base default options for the date pickers
     *
     * @return array
     */
    protected function getCommonDefaults()
    {
        return array(
            'widget'                   => 'single_text',
            'dp_pick_time'             => true,
            'dp_use_current'           => true,
            'dp_min_date'              => '1/1/1900',
            'dp_max_date'              => '',
            'dp_show_today'            => true,
            'dp_language'              => 'en',
            'dp_default_date'          => '',
            'dp_disabled_dates'        => array(),
            'dp_enabled_dates'         => array(),
            'dp_icons'                 => array(
                'time' => 'glyphicon glyphicon-time',
                'date' => 'glyphicon glyphicon-calendar',
                'up'   => 'glyphicon glyphicon-chevron-up',
                'down' => 'glyphicon glyphicon-chevron-down'
            ),
            'dp_use_strict'            => false,
            'dp_side_by_side'          => false,
            'dp_days_of_week_disabled' => array(),
        );
    }

    /**
     * Returns default format for type
     *
     * @return string
     */
    protected abstract function getDefaultFormat();
}