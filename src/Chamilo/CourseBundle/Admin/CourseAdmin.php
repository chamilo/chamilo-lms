<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Chamilo\CoreBundle\Entity\Listener\CourseListener;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CoreBundle\Entity\Course;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chamilo\CourseBundle\ToolChain;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class CourseAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class CourseAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('code', 'text', array(
                //'read_only' => true,
            ))
            ->add(
                'description',
                'textarea',
                array('attr' => array('class' => 'ckeditor'))
            )
            ->add('departmentName')
            ->add(
                'visibility',
                'choice',
                array(
                    'choices' => Course::getStatusList(),
                    'translation_domain' => 'ChamiloCoreBundle'
                )
            )
            ->add('departmentUrl')
            ->add('urls', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code'        => 'sonata.admin.access_url_rel_course'
                )
            )
            ->add('users', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code'        => 'sonata.admin.course_rel_user'
                )
            )
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('code')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('title')
            ->addIdentifier('code')
        ;
    }

    /**
     * Very important in order to save the related entities!
     * @param \Chamilo\CoreBundle\Entity\Course $course
     * @return mixed|void
     */
    public function preUpdate($course)
    {
        $course->setUsers($course->getUsers());
        $course->setUrls($course->getUrls());
        $this->updateTools($course);
    }

    /**
     * @param Course $course
     * @return mixed|void
     */
    public function prePersist($course)
    {
        $this->updateTools($course);
    }

    /***
     * @param Course $course
     */
    public function updateTools($course)
    {
        $toolChain = $this->getToolChain();
        $tools = $toolChain->getTools();

        $currentTools = $course->getTools();

        $addedTools = array();
        if (!empty($currentTools)) {
            foreach ($currentTools as $tool) {
                $addedTools[] = $tool->getName();
            }
        }

        foreach ($tools as $tool) {
            $toolName = $tool->getName();
            if (!in_array($toolName, $addedTools)) {

                $toolEntity = new CTool();
                $toolEntity->setCId($course->getId());
                $toolEntity->setImage($tool->getImage());
                $toolEntity->setName($tool->getName());
                $toolEntity->setLink($tool->getLink());
                $toolEntity->setTarget($tool->getTarget());
                $toolEntity->setCategory($tool->getCategory());

                $course->addTools($toolEntity);
            }
        }
    }

    /**
     * @param ToolChain $chainTool
     */
    public function setToolChain(ToolChain $chainTool)
    {
        $this->toolChain = $chainTool;
    }

    /**
     * @return ToolChain
     */
    public function getToolChain()
    {
        return $this->toolChain;
    }
}
