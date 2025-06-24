<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Tool\ToolChain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tool')]
class ToolController extends AbstractController
{
    /**
     * Updates the table tool and resource_type with the content of tools.yml.
     */
    #[Route(path: '/update', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function profile(ToolChain $toolChain): Response
    {
        $toolChain->createTools();

        return new Response('Updated');
    }
}
