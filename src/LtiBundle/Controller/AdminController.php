<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Form\ExternalToolType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 *
 * @package Chamilo\LtiBundle\Controller
 *
 * @Route("/admin/lti")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class AdminController extends BaseController
{
    use ControllerTrait;

    /**
     * @Route("/", name="chamilo_lti_admin")
     */
    public function adminAction(): Response
    {
        $repo = $this->getDoctrine()->getRepository(ExternalTool::class);
        $tools = $repo->findAll();

        return $this->render('@ChamiloCore/Lti/admin.html.twig', [
            'tools' => $tools,
        ]);
    }

    /**
     * @Route("/add", name="chamilo_lti_admin_add")
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

        return $this->render(
            '@ChamiloCore/Lti/admin_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/edit/{toolId}", name="chamilo_lti_admin_edit", requirements={"toolId"="\d+"})
     *
     * @param int     $toolId
     * @param Request $request
     *
     * @return Response
     */
    public function adminEditAction(int $toolId, Request $request): Response
    {
        $em = $this->getDoctrine()
            ->getManager();
        /** @var ExternalTool $tool */
        $tool = $em->find(ExternalTool::class, $toolId);

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

        return $this->render(
            '@ChamiloCore/Lti/admin_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/delete/{toolId}", name="chamilo_lti_admin_delete", requirements={"toolId"="\d+"})
     *
     * @param int $toolId
     *
     * @return Response
     */
    public function adminDeleteAction(int $toolId): Response
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExternalTool $tool */
        $tool = $em->find(ExternalTool::class, $toolId);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $em->remove($tool);
        $em->flush();

        $this->addFlash('success', $this->trans('External tool deleted'));

        return $this->redirectToRoute('chamilo_lti_admin');
    }
}
