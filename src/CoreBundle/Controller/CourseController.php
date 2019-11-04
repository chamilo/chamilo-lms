<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Form\Type\CourseType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CourseController.
 *
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/add")
     *
     * @Security("has_role('ROLE_TEACHER')")
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $form = $this->createForm(new CourseType());

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $course = $form->getData();
            $em->persist($course);
            $em->flush();

            $this->addFlash('sonata_flash_success', 'Course created');

            return $this->redirectToRoute(
                'chamilo_core_course_welcome',
                ['course' => $course]
            );
        }

        /*return [
            'form' => $form->createView(),
        ];*/
        //return $this->render('ChamiloThemeBundle:Default:index.html.twig');
    }

    /**
     * @Route("/welcome/{course}")
     *
     * @ParamConverter(
     *      "course",
     *      class="ChamiloCoreBundle:Course",
     *      options={"repository_method" = "findOneByCode"}
     * )
     */
    public function welcomeAction(Course $course): Response
    {
        return $this->render('@ChamiloTheme/Course/welcome.html.twig', ['course' => $course]);
    }
}
