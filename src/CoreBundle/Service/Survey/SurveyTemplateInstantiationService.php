<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SurveyTemplateInstantiationService
{
    public function __construct(
        private CSurveyRepository $surveyRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function instantiate(
        CSurveyTemplate $template,
        Course $targetCourse,
        ?Session $targetSession,
        User $actingUser,
    ): CSurvey {
        $availableFrom = new DateTime();
        $availableUntil = (clone $availableFrom)->add(new DateInterval('P10D'));
        $language = trim($targetCourse->getCourseLanguage());
        if ('' === $language) {
            $language = 'en';
        }

        $survey = (new CSurvey())
            ->setCode($this->generateUniqueCode($template, $targetCourse, $targetSession, $language))
            ->setTitle($template->getTitle())
            ->setSubtitle((string) $template->getSubtitle())
            ->setLang($language)
            ->setAvailFrom($availableFrom)
            ->setAvailTill($availableUntil)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setIntro((string) $template->getIntro())
            ->setSurveythanks((string) $template->getSurveyThanks())
            ->setAnonymous($template->getAnonymous())
            ->setShuffle($template->getShuffle())
            ->setOneQuestionPerPage($template->getOneQuestionPerPage())
            ->setDisplayQuestionNumber($template->isDisplayQuestionNumber())
            ->setIsMandatory($template->isMandatory())
            ->setDuration($template->getDuration())
            ->setVisibleResults(0)
            ->setResultsHiddenFromTeacher(false)
            ->setSurveyType(0)
            ->setShowFormProfile(0)
            ->setFormFields('')
            ->setInvited(0)
            ->setAnswered(0)
            ->setCreator($actingUser)
            ->setParent($targetCourse)
            ->addCourseLink($targetCourse, $targetSession)
        ;

        $this->surveyRepository->create($survey);

        /** @var array<int, CSurveyQuestion> $questionMap */
        $questionMap = [];

        /** @var array<int, CSurveyQuestionOption> $optionMap */
        $optionMap = [];

        foreach ($template->getQuestions() as $sourceQuestion) {
            if (!$sourceQuestion instanceof CSurveyTemplateQuestion || null === $sourceQuestion->getId()) {
                continue;
            }

            $question = (new CSurveyQuestion())
                ->setSurvey($survey)
                ->setSurveyQuestion($sourceQuestion->getQuestion())
                ->setSurveyQuestionComment((string) $sourceQuestion->getComment())
                ->setType($sourceQuestion->getType())
                ->setDisplay($sourceQuestion->getDisplay())
                ->setSort($sourceQuestion->getSort())
                ->setSharedQuestionId(0)
                ->setMaxValue((int) ($sourceQuestion->getMaxValue() ?? 0))
                ->setIsMandatory($sourceQuestion->isRequired())
            ;
            $this->entityManager->persist($question);
            $questionMap[$sourceQuestion->getId()] = $question;

            foreach ($sourceQuestion->getOptions() as $sourceOption) {
                if (!$sourceOption instanceof CSurveyTemplateQuestionOption || null === $sourceOption->getId()) {
                    continue;
                }

                $option = (new CSurveyQuestionOption())
                    ->setSurvey($survey)
                    ->setQuestion($question)
                    ->setOptionText($sourceOption->getOptionText())
                    ->setSort($sourceOption->getSort())
                    ->setValue($sourceOption->getValue())
                ;
                $this->entityManager->persist($option);
                $optionMap[$sourceOption->getId()] = $option;
            }
        }

        foreach ($template->getQuestions() as $sourceQuestion) {
            if (!$sourceQuestion instanceof CSurveyTemplateQuestion || null === $sourceQuestion->getId()) {
                continue;
            }

            $question = $questionMap[$sourceQuestion->getId()] ?? null;
            if (!$question instanceof CSurveyQuestion) {
                continue;
            }

            $sourceParent = $sourceQuestion->getParent();
            if ($sourceParent instanceof CSurveyTemplateQuestion && null !== $sourceParent->getId()) {
                $question->setParent($questionMap[$sourceParent->getId()] ?? null);
            }

            $sourceParentOption = $sourceQuestion->getParentOption();
            if ($sourceParentOption instanceof CSurveyTemplateQuestionOption && null !== $sourceParentOption->getId()) {
                $question->setParentOption($optionMap[$sourceParentOption->getId()] ?? null);
            }
        }

        $this->entityManager->flush();

        return $survey;
    }

    private function generateUniqueCode(
        CSurveyTemplate $template,
        Course $course,
        ?Session $session,
        string $language,
    ): string {
        $base = strtolower(trim(strip_tags($template->getTitle())));
        $base = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?? '';
        $base = trim($base, '-_');
        if ('' === $base) {
            $base = 'survey-template-'.(int) $template->getId();
        }
        $base = substr($base, 0, 32);

        $candidate = $base;
        $suffix = 2;
        while ($this->surveyCodeExists($candidate, $language, $course, $session)) {
            $tail = '-'.$suffix;
            $candidate = substr($base, 0, 40 - \strlen($tail)).$tail;
            $suffix++;
        }

        return $candidate;
    }

    private function surveyCodeExists(string $code, string $language, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->surveyRepository
            ->getResourcesByCourse($course, $session, null, null, false, false)
            ->select('COUNT(DISTINCT resource.iid)')
            ->andWhere('resource.code = :code')
            ->andWhere('resource.lang = :language')
            ->setParameter('code', $code)
            ->setParameter('language', $language)
        ;

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
