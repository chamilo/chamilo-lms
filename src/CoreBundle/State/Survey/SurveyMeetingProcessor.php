<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyMeeting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<SurveyMeeting, SurveyMeeting>
 */
final readonly class SurveyMeetingProcessor implements ProcessorInterface
{
    use SurveyCsrfTokenValidationTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private SurveyMeetingProvider $surveyMeetingProvider,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyMeeting
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, SurveyMeetingProvider::CSRF_TOKEN_ID, $payload);

        $course = $this->surveyMeetingProvider->getCourse($request);
        $session = $this->surveyMeetingProvider->getSession($request);
        $operationName = (string) $operation->getName();
        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;

        if ('post_survey_meeting_answer' === $operationName) {
            return $this->submitAnswer($surveyId, $payload, $course, $session, $request);
        }

        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage meeting polls in this context.');
        }

        if ($this->isSurveyEditionGloballyHidden()) {
            throw new AccessDeniedHttpException('Survey edition is disabled by configuration.');
        }

        if ('post_survey_meeting' === $operationName) {
            $survey = $this->createMeetingPoll($payload, $course, $session);
            $this->entityManager->flush();

            return $this->surveyMeetingProvider->buildResponse(
                $survey,
                $course,
                $session,
                $request,
                true,
                true,
                'Meeting poll created.',
            );
        }

        if ('put_survey_meeting' === $operationName) {
            $survey = $this->surveyMeetingProvider->getMeetingSurveyFromCurrentContext($surveyId, $course, $session);
            $this->updateMeetingPoll($survey, $payload);
            $this->entityManager->flush();

            return $this->surveyMeetingProvider->buildResponse(
                $survey,
                $course,
                $session,
                $request,
                true,
                true,
                'Meeting poll updated.',
            );
        }

        throw new BadRequestHttpException('Unsupported meeting poll operation.');
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, mixed $data): array
    {
        $content = trim($request->getContent());
        if ('' !== $content) {
            $payload = json_decode($content, true);
            if (!\is_array($payload)) {
                throw new BadRequestHttpException('The request payload is invalid.');
            }

            return $payload;
        }

        if ($data instanceof SurveyMeeting) {
            return [
                'surveyId' => $data->surveyId,
                'title' => $data->title,
                'description' => $data->description,
                'availableFrom' => $data->availableFrom,
                'availableUntil' => $data->availableUntil,
                'surveyLanguage' => $data->surveyLanguage,
                'slots' => $data->slots,
                'selectedSlots' => $data->selectedSlots,
                'csrfToken' => $data->csrfToken,
            ];
        }

        return [];
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(SurveyMeetingProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createMeetingPoll(array $payload, Course $course, ?Session $session): CSurvey
    {
        $title = trim(strip_tags((string) ($payload['title'] ?? '')));
        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        $availableFrom = $this->parseDate($payload['availableFrom'] ?? null, 'availableFrom');
        $availableUntil = $this->parseDate($payload['availableUntil'] ?? null, 'availableUntil');
        if ($availableFrom > $availableUntil) {
            throw new BadRequestHttpException('The first date should be before the end date.');
        }

        $slots = $this->normalizeSlots($payload['slots'] ?? []);
        if ([] === $slots) {
            throw new BadRequestHttpException('At least one meeting date is required.');
        }

        $language = trim((string) ($payload['surveyLanguage'] ?? '')) ?: $this->getCourseLanguage($course);
        $survey = new CSurvey();
        $survey
            ->setCode($this->generateSurveyCode($title, $language))
            ->setTitle($title)
            ->setSubtitle('')
            ->setLang($language)
            ->setAvailFrom($availableFrom)
            ->setAvailTill($availableUntil)
            ->setIntro((string) ($payload['description'] ?? ''))
            ->setSurveythanks('')
            ->setAnonymous('0')
            ->setVisibleResults(0)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setSurveyType(3)
            ->setShowFormProfile(0)
            ->setFormFields('')
            ->setDisplayQuestionNumber(true)
            ->setOneQuestionPerPage(false)
            ->setShuffle(false)
            ->setSurveyVersion('')
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        $this->surveyRepository->create($survey);
        $this->syncSlots($survey, $slots);

        return $survey;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateMeetingPoll(CSurvey $survey, array $payload): void
    {
        $title = trim(strip_tags((string) ($payload['title'] ?? '')));
        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        $availableFrom = $this->parseDate($payload['availableFrom'] ?? null, 'availableFrom');
        $availableUntil = $this->parseDate($payload['availableUntil'] ?? null, 'availableUntil');
        if ($availableFrom > $availableUntil) {
            throw new BadRequestHttpException('The first date should be before the end date.');
        }

        $slots = $this->normalizeSlots($payload['slots'] ?? []);
        if ([] === $slots) {
            throw new BadRequestHttpException('At least one meeting date is required.');
        }

        $survey
            ->setTitle($title)
            ->setAvailFrom($availableFrom)
            ->setAvailTill($availableUntil)
            ->setIntro((string) ($payload['description'] ?? ''))
            ->setSurveyType(3)
            ->setAnonymous('0')
            ->setVisibleResults(0)
        ;

        $this->syncSlots($survey, $slots);
        $this->entityManager->persist($survey);
    }

    /**
     * @return array<int, array{id: int|null, start: DateTime, end: DateTime}>
     */
    private function normalizeSlots(mixed $slots): array
    {
        if (!\is_array($slots)) {
            throw new BadRequestHttpException('The meeting dates payload is invalid.');
        }

        $normalized = [];
        foreach ($slots as $slot) {
            if (!\is_array($slot)) {
                continue;
            }

            $startValue = $slot['start'] ?? null;
            $endValue = $slot['end'] ?? null;
            if (null === $startValue || null === $endValue || '' === (string) $startValue || '' === (string) $endValue) {
                continue;
            }

            $start = $this->parseDate($startValue, 'slotStart');
            $end = $this->parseDate($endValue, 'slotEnd');
            if ($start >= $end) {
                throw new BadRequestHttpException('Each meeting date must end after it starts.');
            }

            $normalized[] = [
                'id' => isset($slot['id']) && (int) $slot['id'] > 0 ? (int) $slot['id'] : null,
                'start' => $start,
                'end' => $end,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array{id: int|null, start: DateTime, end: DateTime}> $slots
     */
    private function syncSlots(CSurvey $survey, array $slots): void
    {
        $existingSlots = [];
        foreach ($this->surveyMeetingProvider->getOrderedSlots($survey) as $slot) {
            $existingSlots[(int) $slot->getIid()] = $slot;
        }

        $keptSlotIds = [];
        $sort = 1;
        foreach ($slots as $slotData) {
            $slot = null;
            if (null !== $slotData['id'] && isset($existingSlots[$slotData['id']])) {
                $slot = $existingSlots[$slotData['id']];
                $keptSlotIds[] = (int) $slot->getIid();
            }

            if (!$slot instanceof CSurveyQuestion) {
                $slot = new CSurveyQuestion();
                $slot
                    ->setSurvey($survey)
                    ->setType('doodle')
                    ->setDisplay('horizontal')
                    ->setSurveyQuestionComment('')
                    ->setSharedQuestionId(0)
                    ->setMaxValue(0)
                    ->setSurveyGroupPri(0)
                    ->setSurveyGroupSec1(0)
                    ->setSurveyGroupSec2(0)
                    ->setIsMandatory(false)
                ;
                $this->entityManager->persist($slot);
            }

            $slot
                ->setSurveyQuestion($slotData['start']->format('Y-m-d H:i:s').'@@'.$slotData['end']->format('Y-m-d H:i:s'))
                ->setSort($sort)
            ;
            $sort++;
        }

        foreach ($existingSlots as $slotId => $slot) {
            if (!\in_array($slotId, $keptSlotIds, true)) {
                $this->entityManager->remove($slot);
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function submitAnswer(int $surveyId, array $payload, Course $course, ?Session $session, Request $request): SurveyMeeting
    {
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->surveyMeetingProvider->getMeetingSurveyFromCurrentContext($surveyId, $course, $session);
        $user = $this->surveyMeetingProvider->getCurrentUser();
        $this->assertSurveyIsAvailable($survey);
        $invitation = $this->surveyMeetingProvider->getInvitation($survey, $course, $session, $user, $request);
        $selectedSlots = isset($payload['selectedSlots']) && \is_array($payload['selectedSlots'])
            ? array_map('intval', $payload['selectedSlots'])
            : [];

        $slots = $this->surveyMeetingProvider->getOrderedSlots($survey);
        $slotIds = array_map(static fn (CSurveyQuestion $slot): int => (int) $slot->getIid(), $slots);
        $selectedSlots = array_values(array_intersect($selectedSlots, $slotIds));

        $this->removeExistingAnswers($survey, (string) $user->getId(), $request);

        foreach ($slots as $slot) {
            if (!\in_array((int) $slot->getIid(), $selectedSlots, true)) {
                continue;
            }

            $answer = new CSurveyAnswer();
            $answer
                ->setSurvey($survey)
                ->setQuestion($slot)
                ->setUser((string) $user->getId())
                ->setOptionId('1')
                ->setValue(1)
                ->setLpItemId($request->query->getInt('lpItemId'))
                ->setSessionId($request->query->getInt('sid') > 0 ? $request->query->getInt('sid') : null)
            ;
            $this->entityManager->persist($answer);
        }

        $wasAnswered = 1 === (int) $invitation->getAnswered();
        $invitation
            ->setAnswered(1)
            ->setAnsweredAt(new DateTime())
        ;

        if (!$wasAnswered) {
            $survey->setAnswered($survey->getAnswered() + 1);
        }

        $this->entityManager->flush();

        return $this->surveyMeetingProvider->buildResponse(
            $survey,
            $course,
            $session,
            $request,
            false,
            true,
            'Saved.',
        );
    }

    private function removeExistingAnswers(CSurvey $survey, string $userId, Request $request): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->delete(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->andWhere('answer.user = :userId')
            ->andWhere('answer.lpItemId = :lpItemId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('userId', $userId)
            ->setParameter('lpItemId', $request->query->getInt('lpItemId'), Types::INTEGER)
        ;

        $sessionId = $request->query->getInt('sid');
        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('answer.sessionId IS NULL');
        }

        $queryBuilder->getQuery()->execute();
    }

    private function parseDate(mixed $value, string $field): DateTime
    {
        if (null === $value || '' === trim((string) $value)) {
            throw new BadRequestHttpException('The '.$field.' field is required.');
        }

        try {
            $date = new DateTime((string) $value);
        } catch (Throwable) {
            throw new BadRequestHttpException('The '.$field.' field contains an invalid date.');
        }

        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    private function generateSurveyCode(string $title, string $language): string
    {
        $baseCode = strtolower(trim($title));
        $baseCode = preg_replace('/[^a-z0-9_\-]+/', '_', $baseCode) ?? '';
        $baseCode = trim($baseCode, '_-') ?: 'meeting_poll';
        $baseCode = substr($baseCode, 0, 34);
        $code = $baseCode;
        $suffix = 1;

        while ($this->surveyCodeExists($code, $language)) {
            $suffix++;
            $code = substr($baseCode, 0, 34).'_'.$suffix;
        }

        return substr($code, 0, 40);
    }

    private function surveyCodeExists(string $code, string $language): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(survey.iid)')
            ->from(CSurvey::class, 'survey')
            ->andWhere('survey.code = :code')
            ->andWhere('survey.lang = :language')
            ->setParameter('code', $code)
            ->setParameter('language', $language)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function assertSurveyIsAvailable(CSurvey $survey): void
    {
        $now = new DateTime();
        $availableFrom = $survey->getAvailFrom();
        $availableUntil = $survey->getAvailTill();

        if (null !== $availableFrom && $availableFrom > $now) {
            throw new AccessDeniedHttpException('This survey is not open yet.');
        }

        if (null !== $availableUntil && $availableUntil < $now) {
            throw new AccessDeniedHttpException('This survey is already closed.');
        }
    }

    private function canManageSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey');
    }

    private function isSurveyEditionGloballyHidden(): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);

        return true === $value || 'true' === $value || '*' === $value;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function getCourseLanguage(Course $course): string
    {
        if (method_exists($course, 'getCourseLanguage')) {
            return (string) $course->getCourseLanguage();
        }

        return '';
    }
}
