<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\ToolChain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tool")
 */
class ToolController extends AbstractController
{
    /**
     * Updates the table tool and resource_type with the content of tools.yml.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/update", methods={"GET"})
     */
    public function profileAction(ToolChain $toolChain)
    {
        $toolChain->createTools();

        return new Response('Updated');
    }
}
