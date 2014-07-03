<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RouteTypeType extends AbstractType
{
    protected $routeTypes = array();
    protected $translator;

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array();
        foreach ($this->routeTypes as $routeType) {
            $choices[$routeType] = 'route_type.'.$routeType;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'CmfRoutingBundle',
        ));
    }

    /**
     * Register a route type
     *
     * @param string $type
     */
    public function addRouteType($type)
    {
        $this->routeTypes[$type] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cmf_routing_route_type';
    }
}
