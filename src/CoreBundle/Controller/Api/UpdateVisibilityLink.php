<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityLink extends AbstractController
{
    public function __invoke(CLink $link, CLinkRepository $repo): CLink
    {
        $repo->toggleVisibilityPublishedDraft($link);
        $link->toggleVisibility();

        return $link;
    }
}
