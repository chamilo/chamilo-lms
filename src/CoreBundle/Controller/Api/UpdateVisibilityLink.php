<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityLink extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    public function __invoke(CLink $link, CLinkRepository $repo): CLink
    {
        $repo->toggleVisibilityPublishedDraft(
            $link,
            $this->cidReqHelper->getCourseEntity(),
            $this->cidReqHelper->getSessionEntity()
        );
        $link->toggleVisibility();

        return $link;
    }
}
