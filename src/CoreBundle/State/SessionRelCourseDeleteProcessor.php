<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removes a course from a session and keeps Session::nbrCourses right (see RoomAssignmentStateProcessor, which does
 * the same when a course is added).
 *
 * @implements ProcessorInterface<SessionRelCourse, mixed>
 */
final readonly class SessionRelCourseDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $session = $data instanceof SessionRelCourse ? $data->getSession() : null;

        $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);

        if (null !== $session) {
            $count = (int) $this->entityManager
                ->createQuery('SELECT COUNT(src.id) FROM '.SessionRelCourse::class.' src WHERE src.session = :session')
                ->setParameter('session', $session)
                ->getSingleScalarResult()
            ;

            if ($session->getNbrCourses() !== $count) {
                $session->setNbrCourses($count);
                $this->entityManager->flush();
            }
        }

        return $result;
    }
}
