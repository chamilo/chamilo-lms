<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataTransformer;

use Chamilo\CoreBundle\ApiResource\CourseTool;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\CourseFromRequestTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CourseToolDataTranformer
{
    use CourseFromRequestTrait;

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        protected readonly ToolChain $toolChain,
    ) {}

    public function transform(CTool $object, ?AbstractTool $resolvedTool = null): CourseTool
    {
        $toolEntity = $object->getTool();

        $toolModel = $resolvedTool ?? $this->toolChain->getToolFromName(
            $toolEntity->getTitle()
        );

        $course = $this->getCourse();

        $titleToShow = trim($toolModel->getTitleToShow());
        if ('' === $titleToShow) {
            $titleToShow = $object->getTitle();
        }

        $cTool = new CourseTool();
        $cTool->iid = $object->getIid();
        $cTool->title = $titleToShow;
        $cTool->visibility = $object->getVisibility();
        $cTool->resourceNode = $object->resourceNode;
        $cTool->illustrationUrl = $object->illustrationUrl;
        $cTool->url = $this->generateToolUrl($toolModel, $course);
        $cTool->tool = $toolModel;

        return $cTool;
    }

    private function generateToolUrl(AbstractTool $tool, Course $course): string
    {
        $link = $tool->getLink();

        if (str_contains($link, ':nodeId')) {
            $nodeId = (string) $course->getResourceNode()->getId();
            $link = str_replace(':nodeId', $nodeId, $link);
        }

        $query = http_build_query([
            'cid' => $this->getCourse()->getId(),
            'sid' => $this->getSession()?->getId(),
            'gid' => 0,
        ]);

        $separator = str_contains($link, '?') ? '&' : '?';

        return $link.$separator.$query;
    }
}
