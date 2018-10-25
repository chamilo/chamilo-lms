<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class ResourceLinkAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class ResourceLinkAdmin extends AbstractAdmin
{
    /**
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('resourceNode')
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('resourceNode')
            ->add('visibility', ChoiceType::class, ['choices' => ResourceLink::getVisibilityList()])
            ->add(
                'resourceRight',
                ModelAutocompleteType::class,
                ['property' => 'id', 'btn_add' => 'link_add', 'multiple' => true]
            )
            //->add('resourceNode', ModelType::class, ['property' => 'id', 'btn_add' => 'link_add'])
            ->end()
        ;
    }

//    /**
//     * @param DatagridMapper $datagridMapper
//     */
//    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
//    {
//        $datagridMapper
//            ->add('url')
//        ;
//    }
//

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('resourceNode')
            ->addIdentifier('visibility')
        ;
    }
}
