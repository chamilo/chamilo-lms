<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ForcedLoginRedirectHelper;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasConfiguration;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasLoginFlow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CasController extends AbstractController
{
    #[Route('/connect/cas', name: 'chamilo.cas_start', methods: ['GET'])]
    public function start(Request $request, CasConfiguration $configuration, CasLoginFlow $flow, AuthenticationConfigHelper $authConfig, ForcedLoginRedirectHelper $forcedRedirect): Response
    {
        $portal = $configuration->resolvePortal($request);
        $config = null !== $portal ? $configuration->get($portal) : null;
        $forced = $forcedRedirect->isEscapeActive($request) ? null : $authConfig->getForcedLoginMethod($portal);
        if (null === $config || !\in_array($forced, [null, 'cas'], true)) {
            throw $this->createAccessDeniedException();
        }
        $current = $this->getUser();
        if ($current instanceof User && User::ANONYMOUS !== $current->getStatus()) {
            return $this->redirectToRoute('index');
        }

        return new RedirectResponse($flow->start($request, $portal, $config), Response::HTTP_SEE_OTHER, ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    #[Route('/connect/cas/check', name: 'chamilo.cas_check', methods: ['GET'])]
    public function check(): Response
    {
        // A signed-in browser must not be switched to another CAS identity.
        $current = $this->getUser();
        if ($current instanceof User && User::ANONYMOUS !== $current->getStatus()) {
            return $this->redirectToRoute('index');
        }

        // Authentication is performed by CasAuthenticator, never by a controller.
        throw $this->createAccessDeniedException();
    }
}
