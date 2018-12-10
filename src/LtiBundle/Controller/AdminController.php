<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Form\ExternalToolType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController.
 *
 * @package Chamilo\LtiBundle\Controller
 */
class AdminController extends BaseController
{
    /**
     * @Route("/", name="chamilo_lti_admin")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function adminAction(): Response
    {
        $repo = $this->getDoctrine()->getRepository('ChamiloLtiBundle:ExternalTool');
        $tools = $repo->findAll();

        return $this->render('@ChamiloTheme/Lti/admin.html.twig', ['tools' => $tools]);
    }

    /**
     * @Route("/add", name="chamilo_lti_admin_add")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function adminAddAction(Request $request): Response
    {
        $form = $this->createForm(ExternalToolType::class, new ExternalTool());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExternalTool $tool */
            $tool = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($tool);
            $em->flush();

            $this->addFlash('success', $this->trans('External tool added'));

            return $this->redirectToRoute('chamilo_lti_admin');
        }

        $breadcrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadcrumb->addChild(
            $this->trans('Administration'),
            ['route' => 'administration']
        );
        $breadcrumb->addChild(
            $this->trans('External tools'),
            ['route' => 'chamilo_lti_admin']
        );
        $breadcrumb->addChild('Add external tool');

        return $this->render(
            '@ChamiloTheme/Lti/admin_form.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/edit/{toolId}", name="chamilo_lti_admin_edit", requirements={"toolId"="\d+"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int     $toolId
     * @param Request $request
     *
     * @return Response
     */
    public function adminEditAction($toolId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExternalTool $tool */
        $tool = $em->find('ChamiloLtiBundle:ExternalTool', $toolId);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ExternalToolType::class, $tool);
        $form->get('shareName')->setData($tool->isSharingName());
        $form->get('shareEmail')->setData($tool->isSharingEmail());
        $form->get('sharePicture')->setData($tool->isSharingPicture());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExternalTool $tool */
            $tool = $form->getData();

            $em->persist($tool);
            $em->flush();

            $this->addFlash('success', $this->trans('External tool edited'));

            return $this->redirectToRoute('chamilo_lti_admin');
        }

        $breadcrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadcrumb->addChild(
            $this->trans('Administration'),
            ['route' => 'administration']
        );
        $breadcrumb->addChild(
            $this->trans('External tools'),
            ['route' => 'chamilo_lti_admin']
        );
        $breadcrumb->addChild('Edit external tool');

        return $this->render(
            '@ChamiloTheme/Lti/admin_form.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/delete/{toolId}", name="chamilo_lti_admin_delete", requirements={"toolId"="\d+"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int $toolId
     *
     * @return Response
     */
    public function adminDeleteAction($toolId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExternalTool $tool */
        $tool = $em->find('ChamiloLtiBundle:ExternalTool', $toolId);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $em->remove($tool);
        $em->flush();

        $this->addFlash('success', $this->trans('External tool deleted'));

        return $this->redirectToRoute('chamilo_lti_admin');
    }
}
