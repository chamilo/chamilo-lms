<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Source\Entity;
use Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar\Introduction;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CToolIntroController.
 *
 * @package Chamilo\CourseBundle\Controller
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @Route("/introduction")
 */
class CToolIntroController extends ToolBaseController
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        $breadCrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadCrumb->addChild($this->trans('Introduction'));

        // Creates a simple grid based on your entity (ORM)
        $source = new Entity('ChamiloCourseBundle:CToolIntro');

        // Get a Grid instance
        $grid = $this->get('grid');

        // Attach the source to the grid
        $grid->setSource($source);

        $rowUpdateAction = new RowAction(
            $this->trans('Update'),
            'chamilo_course_ctoolintro_update',
            false,
            '_self',
            ['class' => 'btn'],
            null//$role
        );

        $rowUpdateAction->setRouteParameters(
            ['iid', 'course' => $this->getCourse()->getCode()]
        );

        $rowAction = new RowAction(
            $this->trans('Delete'),
            'chamilo_course_ctoolintro_delete',
            true,
            '_self',
            ['class' => 'btn'],
            null//$role
        );

        $rowAction->setRouteParameters(
            ['iid', 'course' => $this->getCourse()->getCode()]
        );

        $grid->addRowAction($rowUpdateAction);
        $grid->addRowAction($rowAction);

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'ChamiloCourseBundle:CToolIntro:grid.html.twig'
        );
    }

    /**
     * @Route("/{tool}/create")
     *
     * @Template("ChamiloCourseBundle:CToolIntro:create.html.twig")
     *
     * @return array
     */
    public function createAction(Request $request, $tool)
    {
        // Breadcrumb
        $breadCrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadCrumb->addChild(
            $this->trans('Introduction'),
            [
                'route' => 'chamilo_course_ctoolintro_index',
                'routeParameters' => [
                    'course' => $this->getCourse()->getCode(),
                ],
            ]
        );
        $breadCrumb->addChild($this->trans('Create'));

        $course = $this->getCourse();
        $session = $this->getSession();

        $toolIntro = new CToolIntro();
        $toolIntro
            ->setSessionId(0)
            ->setTool($tool)
            ->setCId($course->getId());

        if ($session) {
            $toolIntro->setSessionId($session->getId());
        }

        // Declared in forms.yml
        $formService = $this->get('chamilo_course.form.type.c_tool_intro');

        $form = $this->createForm($formService, $toolIntro);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($toolIntro);
            $em->flush();
            $this->addFlash('success', $this->trans('Saved'));

            return $this->redirectToRoute(
                'chamilo_course_ctoolintro_update',
                ['course' => $course->getCode(), 'iid' => $toolIntro->getId()]
            );
        }

        return ['form' => $form->createView(), 'tool' => $tool];
    }

    /**
     * @Route("/{iid}/update/", methods={"GET|POST"})
     *
     * @Template("ChamiloCourseBundle:CToolIntro:update.html.twig")
     *
     * @return Response
     */
    public function updateAction($iid, Request $request)
    {
        // Breadcrumb
        $breadCrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadCrumb->addChild(
            $this->trans('Introduction'),
            [
                'route' => 'chamilo_course_ctoolintro_index',
                'routeParameters' => [
                    'course' => $this->getCourse()->getCode(),
                ],
            ]
        );
        $breadCrumb->addChild($this->trans('Update'));

        $course = $this->getCourse();

        $em = $this->get('doctrine')->getManager();
        $criteria = [
            'iid' => $iid,
        ];

        $formService = $this->get('chamilo_course.form.type.c_tool_intro');

        $toolIntro = $em->getRepository('ChamiloCourseBundle:CToolIntro')->findOneBy($criteria);
        $form = $this->createForm($formService, $toolIntro);
        $tool = $toolIntro->getTool();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($toolIntro);
            $em->flush();
            $this->addFlash('success', $this->trans('Updated'));

            return $this->redirectToRoute(
                'chamilo_course_ctoolintro_update',
                ['course' => $course->getCode(), 'tool' => $tool, 'iid' => $iid]
            );
        }

        return [
            'tool' => $tool,
            'iid' => $iid,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{iid}/delete", methods={"GET"})
     *
     * @param int     $iid
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($iid, Request $request)
    {
        $criteria = [
            'iid' => $iid,
        ];

        $doctrine = $this->getDoctrine();
        $toolIntro = $doctrine->getRepository('ChamiloCourseBundle:CToolIntro')->findOneBy($criteria);
        if ($toolIntro) {
            $doctrine->getManager()->remove($toolIntro);
            $doctrine->getManager()->flush();
            $this->addFlash('success', $this->trans("IntroductionTextDeleted"));
        }

        return $this->redirectCourseHome();
    }
}
