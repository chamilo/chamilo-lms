<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CTool;
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

            $courseTool = $this->getOrCreateCourseTool($course, $toolName, $session);

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
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function findCourseTool(Course $course, string $toolName, ?Session $session): ?CTool
    {
        return $this->entityManager->getRepository(CTool::class)->findOneBy([
            'title' => $toolName,
            'course' => $course,
            'session' => $session,
        ]);
    }

    /**
     * Returns the course tool for the given title/context, creating it on the
     * fly when it does not exist yet. Returns null only when the base tool
     * definition is unknown.
     */
    private function getOrCreateCourseTool(Course $course, string $toolName, ?Session $session): ?CTool
    {
        $existing = $this->findCourseTool($course, $toolName, $session);
        if ($existing) {
            return $existing;
        }

        $tool = $this->entityManager->getRepository(Tool::class)->findOneBy(['title' => $toolName]);
        if (!$tool) {
            return null;
        }

        $courseTool = (new CTool())
            ->setTool($tool)
            ->setTitle($toolName)
            ->setCourse($course)
            ->setPosition(1)
            ->setParent($course)
            ->setCreator($course->getCreator())
            ->setSession($session)
            ->addCourseLink($course)
        ;

        $this->entityManager->persist($courseTool);
        $this->entityManager->flush();

        return $courseTool;
    }
}
