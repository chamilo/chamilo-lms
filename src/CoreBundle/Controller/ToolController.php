<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Tool\ToolChain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tool')]
class ToolController extends AbstractController
{
    /**
     * Updates the table tool and resource_type with the content of tools.yml.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    #[Route(path: '/update', methods: ['GET'])]
    public function profile(ToolChain $toolChain): Response
    {
        $toolChain->createTools();

        return new Response('Updated');
    }
}
