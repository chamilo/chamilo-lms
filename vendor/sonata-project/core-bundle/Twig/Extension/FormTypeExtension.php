<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

class FormTypeExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var bool
     */
    private $wrapFieldsWithAddons;

    /**
     * @param $formType
     */
    public function __construct($formType)
    {
        $this->wrapFieldsWithAddons = (true === $formType || $formType === 'standard');
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return array(
            'wrap_fields_with_addons' => $this->wrapFieldsWithAddons,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sonata_core_wrapping';
    }
}
