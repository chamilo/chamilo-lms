<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

/**
 * Class FormTypeExtension.
 */
class FormTypeExtension extends \Twig_Extension
{
    /**
     * @var bool
     */
    private $wrapFieldsWithAddons;

    public function __construct($formType)
    {
        $this->wrapFieldsWithAddons = ($formType == 'standard');
    }

    public function getGlobals()
    {
        return array(
            'wrap_fields_with_addons' => $this->wrapFieldsWithAddons,
        );
    }

    public function getName()
    {
        return 'sonata_core_wrapping';
    }
}
