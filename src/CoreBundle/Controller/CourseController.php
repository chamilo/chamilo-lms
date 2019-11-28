<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Form\Type\CourseType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
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
        exit;
        $form = $this->createForm(CourseType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $course = $form->getData();
            /*$em->persist($course);
            $em->flush();*/
            $this->addFlash('sonata_flash_success', 'Course created');

            return $this->redirectToRoute(
                'chamilo_core_course_welcome',
                ['cid' => $course->getId()]
            );
        }

        return $this->render('ChamiloThemeBundle:Course:add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Redirects legacy /courses/ABC/index.php to /courses/1/ (where 1 is the course id) see CourseHomeController
     *
     * @Route("/{courseCode}/index.php", name="chamilo_core_course_home_redirect")
     *
     * @Entity("course", expr="repository.findOneByCode(courseCode)")
     */
    public function homeRedirectAction(Course $course): Response
    {
        return $this->redirectToRoute('chamilo_core_course_home', ['cid' => $course->getId()]);
    }

    /**
     * @Route("/{cid}/welcome", name="chamilo_core_course_welcome")
     *
     * @Entity("course", expr="repository.find(cid)")
     */
    public function welcomeAction(Course $course): Response
    {
        return $this->render('@ChamiloTheme/Course/welcome.html.twig', ['course' => $course]);
    }
}
