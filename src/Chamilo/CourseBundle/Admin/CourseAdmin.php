<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\ToolChain;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class CourseAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class CourseAdmin extends Admin
{
    protected $toolChain;

    /**
     * Setting default values
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $instance = parent::getNewInstance();
        $instance->setVisibility('2');
        $instance->setCourseLanguage($this->getTranslator()->getLocale());

        return $instance;
    }

    /**
     * Very important in order to save the related entities while updating.
     *
     * @param \Chamilo\CoreBundle\Entity\Course $course
     *
     * @return mixed|void
     */
    public function preUpdate($course)
    {
        $course->setUsers($course->getUsers());
        $course->setUrls($course->getUrls());
        $this->updateTools($course);
    }

    /**
     *  Very important in order to save the related entities while creation.
     *
     * @param Course $course
     *
     * @return mixed|void
     */
    public function prePersist($course)
    {
        $course->setUsers($course->getUsers());
        $course->setUrls($course->getUrls());
        $this->updateTools($course);
    }

    /*
     * Generate tool inside the course
     * @param Course $course
     */
    public function updateTools($course)
    {
        $toolChain = $this->getToolChain();
        $tools = $toolChain->getTools();
        $currentTools = $course->getTools();

        // @todo use
        //$toolChain->addToolsInCourse($course);

        $addedTools = [];
        if (!empty($currentTools)) {
            foreach ($currentTools as $tool) {
                $addedTools[] = $tool->getName();
            }
        }

        foreach ($tools as $tool) {
            $toolName = $tool->getName();
            if (!in_array($toolName, $addedTools)) {
                $toolEntity = new CTool();
                $toolEntity
                    ->setCId($course->getId())
                    ->setImage($tool->getImage())
                    ->setName($tool->getName())
                    ->setLink($tool->getLink())
                    ->setTarget($tool->getTarget())
                    ->setCategory($tool->getCategory())
                ;

                $course->addTools($toolEntity);
            }
        }
    }

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

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('code', 'text', [
                //'read_only' => true,
            ])
            ->add('description', 'ckeditor')
            ->add('courseLanguage', 'language')
            ->add('departmentName')
            ->add(
                'visibility',
                'choice',
                [
                    'choices' => Course::getStatusList(),
                    'translation_domain' => 'ChamiloCoreBundle',
                ]
            )
            ->add('departmentUrl', 'url', ['required' => false])
            ->add(
                'urls',
                'sonata_type_collection',
                [
                    'cascade_validation' => true,
                ],
                [
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit' => 'inline',
                    'inline' => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code' => 'sonata.admin.access_url_rel_course',
                ]
            )
            ->add(
                'users',
                'sonata_type_collection',
                [
                    'cascade_validation' => true,
                ],
                [
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit' => 'inline',
                    'inline' => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code' => 'sonata.admin.course_rel_user',
                ]
            )
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('code')
            ->add(
                'visibility',
                null,
                [],
                'choice',
                ['choices' => Course::getStatusList()]
            )
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
            ->addIdentifier('code')
            ->add('courseLanguage')
            ->add('visibility', 'choice', [
                'choices' => Course::getStatusList(),
            ])
        ;
    }
}
