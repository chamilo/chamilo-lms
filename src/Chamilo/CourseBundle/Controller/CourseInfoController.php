<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactoryInterface;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Chamilo\CoreBundle\Entity\Course;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class CourseInfoController
 * @package Chamilo\CourseBundle\Controller
 */
class CourseInfoController extends ToolBaseController
{
    /**
     * @Route("/course_info")

     * @ParamConverter("course", class="ChamiloCoreBundle:Course", options={"repository_method" = "findOneByCode"})
     * @Template
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $course = $this->getCourse();

        $form = $this->createFormBuilder($course)
            ->add('title', 'text')
            ->add('description', 'textarea')
            ->add('category_code', 'text')
            ->add('course_language', 'language')
            ->add('department_name', 'text')
            ->add('department_url', 'url')
            ->add('disk_quota', 'text')
            ->add('save', 'submit', array('label' => 'Update'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $course = $form->getData();
            $em->persist($course);
            $em->flush();
        }
        return array(
            'form' => $form->createView(),
        );
    }
}
