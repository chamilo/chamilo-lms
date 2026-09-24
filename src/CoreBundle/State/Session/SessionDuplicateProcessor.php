<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Session;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Dto\SessionDuplicateInput;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use SessionManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Port of the 1.11.x create_session_from_model webservice: SessionManager::copy() clones the
 * model session (courses, general coaches, extra fields and, on request, the session-specific
 * course content), then the copy gets the requested title and dates and the model's promotion,
 * session admins, subscription notification and course order, which copy() does not carry over.
 * Like the legacy webservice, course coaches, HR managers and students are not subscribed.
 *
 * @implements ProcessorInterface<SessionDuplicateInput, Session>
 */
final readonly class SessionDuplicateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SessionRepository $sessionRepository,
        private ExtraFieldRepository $extraFieldRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Session
    {
        \assert($data instanceof SessionDuplicateInput);

        $model = $this->sessionRepository->find((int) $uriVariables['id']);
        if (null === $model) {
            throw new NotFoundHttpException('Session not found.');
        }

        // Everything the request can get wrong is checked before copying, so a rejected
        // request never leaves a half-configured session behind.
        if (null !== $this->sessionRepository->findOneBy(['title' => $data->title])) {
            throw new UnprocessableEntityHttpException('Session title already exists.');
        }

        foreach (array_keys($data->extraFields) as $variable) {
            if (null === $this->extraFieldRepository->findByVariable(ExtraField::SESSION_FIELD_TYPE, (string) $variable)) {
                throw new UnprocessableEntityHttpException('Unknown session extra field: '.$variable);
            }
        }

        $newSessionId = SessionManager::copy(
            (int) $model->getId(),
            true,
            false,
            false,
            false,
            $data->copySessionContent
        );

        if (!$newSessionId) {
            throw new RuntimeException('The session could not be duplicated.');
        }

        $newSession = $this->sessionRepository->find((int) $newSessionId);
        if (null === $newSession) {
            throw new RuntimeException('The duplicated session could not be loaded.');
        }

        // copy() writes part of the copy through legacy SQL, outside the unit of work.
        $this->entityManager->refresh($newSession);

        $startDate = (clone $data->startDate)->setTimezone(new DateTimeZone('UTC'));
        $endDate = (clone $data->endDate)->setTimezone(new DateTimeZone('UTC'));

        $newSession
            ->setTitle($data->title)
            ->setAccessStartDate($startDate)
            ->setAccessEndDate($endDate)
            ->setDisplayStartDate(clone $startDate)
            ->setDisplayEndDate(clone $endDate)
            ->setCoachAccessStartDate(clone $startDate)
            ->setCoachAccessEndDate(clone $endDate)
            ->setPromotion($model->getPromotion())
            ->setSendSubscriptionNotification($model->getSendSubscriptionNotification())
        ;

        $this->copySessionAdmins($model, $newSession);
        $this->copyCoursePositions($model, $newSession);

        $this->entityManager->flush();

        foreach ($data->extraFields as $variable => $value) {
            SessionManager::update_session_extra_field_value($newSession->getId(), (string) $variable, $value ?? '');
        }

        return $newSession;
    }

    /**
     * copy() makes the caller the session admin of the copy; the legacy webservice kept the
     * model's session admin instead, and fell back to the caller only when the model had none.
     */
    private function copySessionAdmins(Session $model, Session $newSession): void
    {
        $modelAdmins = $model->getSessionAdmins();
        if ($modelAdmins->isEmpty()) {
            return;
        }

        foreach ($newSession->getGeneralAdminsSubscriptions() as $subscription) {
            if (!$model->hasUserAsSessionAdmin($subscription->getUser())) {
                $newSession->removeUserSubscription($subscription);
            }
        }

        foreach ($modelAdmins as $admin) {
            if (!$newSession->hasUserAsSessionAdmin($admin)) {
                $newSession->addSessionAdmin($admin);
            }
        }
    }

    private function copyCoursePositions(Session $model, Session $newSession): void
    {
        $positions = [];
        foreach ($model->getCourses() as $sessionRelCourse) {
            $positions[$sessionRelCourse->getCourse()->getId()] = $sessionRelCourse->getPosition();
        }

        foreach ($newSession->getCourses() as $sessionRelCourse) {
            $courseId = $sessionRelCourse->getCourse()->getId();
            if (isset($positions[$courseId])) {
                $sessionRelCourse->setPosition($positions[$courseId]);
            }
        }
    }
}
