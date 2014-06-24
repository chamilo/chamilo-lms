<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Admin\Extension;

use Sonata\AdminBundle\Admin\AdminExtension;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Admin extension to add routes tab to content implementing the
 * RouteReferrersInterface.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class RouteReferrersExtension extends AdminExtension
{
    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('form.group_routes', array(
                'translation_domain' => 'CmfRoutingBundle',
            ))
            ->add(
                'routes',
                'sonata_type_collection',
                array(),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                ))
            ->end()
        ;
    }
}
