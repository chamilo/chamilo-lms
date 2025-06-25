<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityLinkCategory extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    public function __invoke(CLinkCategory $linkCategory, CLinkCategoryRepository $repo): CLinkCategory
    {
        $repo->toggleVisibilityPublishedDraft(
            $linkCategory,
            $this->cidReqHelper->getDoctrineCourseEntity(),
            $this->cidReqHelper->getDoctrineSessionEntity()
        );
        $linkCategory->toggleVisibility();

        return $linkCategory;
    }
}
