<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Form\ExternalToolType;
use Chamilo\LtiBundle\Repository\ExternalToolRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Disabled until the ImsLti plugin integration is completed. Restore the #[Route] attributes
// (class prefix '/admin/lti' and the four actions below) to bring this admin UI back.
#[IsGranted('ROLE_ADMIN')]
class AdminController extends BaseController
{
    use ControllerTrait;
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ExternalToolRepository $externalToolRepository
    ) {}

    public function admin(): Response
    {
        $tools = $this->externalToolRepository->findAll();

        return $this->render('@ChamiloCore/Lti/admin.html.twig', [
            'tools' => $tools,
        ]);
    }

    public function adminAdd(Request $request): Response
    {
        $form = $this->createForm(ExternalToolType::class, new ExternalTool());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExternalTool $tool */
            $tool = $form->getData();

            $this->externalToolRepository->create($tool);

            $this->addFlash('success', $this->trans('External tool added'));

            return $this->redirectToRoute('chamilo_lti_admin');
        }

        return $this->render(
            '@ChamiloCore/Lti/admin_form.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    public function adminEdit(int $toolId, Request $request): Response
    {
        $em = $this->managerRegistry
            ->getManager()
        ;

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
                'form' => $form,
            ]
        );
    }

    public function adminDelete(int $toolId): Response
    {
        $em = $this->managerRegistry->getManager();

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
