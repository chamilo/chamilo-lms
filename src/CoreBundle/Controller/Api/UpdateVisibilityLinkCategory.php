<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityLinkCategory extends AbstractController
{
    public function __invoke(CLinkCategory $linkCategory, CLinkCategoryRepository $repo): CLinkCategory
    {
        $repo->toggleVisibilityPublishedDraft($linkCategory);
        $linkCategory->toggleVisibility();

        return $linkCategory;
    }
}
