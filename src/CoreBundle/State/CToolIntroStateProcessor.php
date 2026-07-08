<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CToolIntro, CToolIntro>
 */
final class CToolIntroStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly CToolIntroHelper $toolIntroHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CToolIntro
    {
        \assert($data instanceof CToolIntro);

        $course = $this->cidReqHelper->getDoctrineCourseEntity();

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        if ($operation instanceof Post) {
            $toolName = trim($data->getToolName());

            if ('' === $toolName) {
                $toolName = 'course_homepage';
            }

            $courseTool = $this->toolIntroHelper->getOrCreateCourseTool($course, $toolName, $session);

            if (!$courseTool) {
                throw new NotFoundHttpException(\sprintf('Course tool "%s" not found.', $toolName));
            }

            $data->setCourseTool($courseTool);

            // Get-or-create: if an introduction already exists for this tool,
            // return it untouched (editing is done through PUT, so POST never
            // overwrites). Otherwise set the parent course so ResourceListener
            // creates the resource node and its course link on persist.
            $existing = $this->entityManager->getRepository(CToolIntro::class)
                ->findOneBy(['courseTool' => $courseTool])
            ;

            if (null !== $existing) {
                $data = $existing;
            } else {
                $data->setParent($course);
                $data->addCourseLink($course, $session);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
