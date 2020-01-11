<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Entity\CTool;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Class CourseAdmin.
 */
class CourseAdmin extends AbstractAdmin
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
        //$instance->setCourseLanguage($this->getLabelTranslatorStrategy()->getLocale());

        return $instance;
    }

    /**
     * Very important in order to save the related entities while updating.
     *
     * @param Course $course
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
    public function updateTools(Course $course)
    {
        //$toolChain = $this->getToolChain();
        //$tools = $toolChain->getTools();
        $currentTools = $course->getTools();

        // @todo use
        //$toolChain->addToolsInCourse($course);

        $addedTools = [];
        if (!empty($currentTools)) {
            foreach ($currentTools as $tool) {
                $addedTools[] = $tool->getName();
            }
        }

        /*foreach ($tools as $tool) {
            $toolName = $tool->getName();
            if (!in_array($toolName, $addedTools)) {
                $toolEntity = new CTool();
                $toolEntity
                    ->setCourse($course)
                    ->setName($tool->getName())
                    ->setCategory($tool->getCategory())
                ;

                $course->addTools($toolEntity);
            }
        }*/
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
            ->add('code', TextType::class, [
                //'read_only' => true,
            ])
            ->add('description', CKEditorType::class)
            ->add('courseLanguage', LanguageType::class)
            ->add('departmentName')
            ->add(
                'visibility',
                ChoiceType::class,
                [
                    'choices' => Course::getStatusList(),
                    'translation_domain' => 'ChamiloCoreBundle',
                ]
            )
            ->add('departmentUrl', UrlType::class, ['required' => false])
            ->add(
                'urls',
                CollectionType::class,
                [
                    //'cascade_validation' => true,
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
                CollectionType::class,
                [
                    //'cascade_validation' => true,
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
                ChoiceType::class,
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
            ->add('visibility', ChoiceType::class, [
                'choices' => Course::getStatusList(),
            ])
        ;
    }
}
