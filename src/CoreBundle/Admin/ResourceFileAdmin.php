<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Class ResourceFileAdmin.
 */
class ResourceFileAdmin extends AbstractAdmin
{
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('size')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $router = $this->getRouteGenerator();

        $fileOptions = [
            'required' => true,
            'allow_delete' => false,
            'download_uri' => static function (ResourceFile $file) use ($router) {
                $resourceNode = $file->getResourceNode();
                $params = [
                    'tool' => $resourceNode->getResourceType()->getTool(),
                    'type' => $resourceNode->getResourceType(),
                    'id' => $resourceNode->getId(),
                    'mode' => 'download',
                ];

                return $router->generate('chamilo_core_resource_view_file', $params);
            },
        ];
        $formMapper
            ->add('file', VichImageType::class, $fileOptions)
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('mimeType')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('name')
            ->add('size')
            ->add('mimeType')

        ;
    }
}
