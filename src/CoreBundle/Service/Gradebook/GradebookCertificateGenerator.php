<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookScoreCalculator;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const DATE_ATOM;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PATHINFO_FILENAME;

final readonly class GradebookCertificateGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GradebookScoreCalculator $scoreCalculator,
        private GradebookCertificateRepository $certificateRepository,
        private CDocumentRepository $documentRepository,
        private SettingsManager $settingsManager,
        private PluginHelper $pluginHelper,
        private GradebookSkillAwarder $skillAwarder,
        private LegacyGradebookCertificateBridge $legacyCertificateBridge,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function usesCustomCertificate(Course $course): bool
    {
        if (!$this->pluginHelper->isPluginEnabled('CustomCertificate')) {
            return false;
        }

        return $this->isCourseSettingEnabled($course, 'customcertificate_course_enable')
            || $this->isCourseSettingEnabled($course, 'use_certificate_default');
    }

    /**
     * @return array{eligible: bool, score: float, minimumScore: float, reason: string}
     */
    public function getAcademicEligibility(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        $result = $this->scoreCalculator->calculateCategory($category, $user, $course, $session);
        $score = null !== $result['percentage'] ? round((float) $result['percentage'], 2) : 0.0;
        $minimumScore = (float) ($category->getCertifMinScore() ?? 0);

        if ($minimumScore > 0.0 && $score < $minimumScore) {
            return [
                'eligible' => false,
                'score' => $score,
                'minimumScore' => $minimumScore,
                'reason' => 'The learner has not reached the minimum certificate score.',
            ];
        }

        if (!$this->meetsEvaluationMinimumScores($category, $user)) {
            return [
                'eligible' => false,
                'score' => $score,
                'minimumScore' => $minimumScore,
                'reason' => 'The learner has not reached all required evaluation minimum scores.',
            ];
        }

        return [
            'eligible' => true,
            'score' => $score,
            'minimumScore' => $minimumScore,
            'reason' => '',
        ];
    }

    /**
     * @return array{eligible: bool, score: float, minimumScore: float, reason: string}
     */
    public function getEligibility(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        $eligibility = $this->getAcademicEligibility($category, $user, $course, $session);
        if (!$eligibility['eligible']) {
            return $eligibility;
        }

        if (!$category->getGenerateCertificates()) {
            return [
                'eligible' => false,
                'score' => $eligibility['score'],
                'minimumScore' => $eligibility['minimumScore'],
                'reason' => 'Certificate generation is disabled for this Gradebook category.',
            ];
        }

        return $eligibility;
    }

    public function generate(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): GradebookCertificate {
        $eligibility = $this->getEligibility($category, $user, $course, $session);
        if (!$eligibility['eligible']) {
            throw new RuntimeException($eligibility['reason']);
        }

        $template = $this->getTemplateHtml($category);
        if ('' === trim($template)) {
            throw new RuntimeException('The certificate template could not be loaded.');
        }

        if ($this->requiresLegacyTemplateCompatibility($template)) {
            return $this->legacyCertificateBridge->generate($category, $user);
        }

        $this->skillAwarder->award($category, $user, $course, $session);

        $existing = $this->certificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $user->getId(),
        );
        $issuedAt = $existing instanceof GradebookCertificate ? $existing->getCreatedAt() : new DateTime();

        $initialHtml = $this->renderTemplate(
            $template,
            $category,
            $user,
            $course,
            $session,
            $eligibility['score'],
            $issuedAt,
            '',
        );

        $certificate = $this->certificateRepository->upsertCertificateResource(
            (int) $category->getId(),
            (int) $user->getId(),
            $eligibility['score'],
            $initialHtml,
        );

        $summary = $this->normalizeCertificate($certificate, false);
        $viewUrl = (string) ($summary['viewUrl'] ?? '');
        $finalHtml = $this->renderTemplate(
            $template,
            $category,
            $user,
            $course,
            $session,
            $eligibility['score'],
            $certificate->getCreatedAt(),
            $viewUrl,
        );

        return $this->certificateRepository->upsertCertificateResource(
            (int) $category->getId(),
            (int) $user->getId(),
            $eligibility['score'],
            $finalHtml,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCertificateSummary(
        GradebookCategory $category,
        User $user,
        bool $hideDownload,
    ): ?array {
        $certificate = $this->certificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $user->getId(),
        );

        return $certificate instanceof GradebookCertificate
            ? $this->normalizeCertificate($certificate, $hideDownload)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeCertificate(GradebookCertificate $certificate, bool $hideDownload): array
    {
        $path = trim((string) $certificate->getPathCertificate());
        $hash = pathinfo(basename($path), PATHINFO_FILENAME);
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $hash)) {
            $hash = '';
        }

        return [
            'id' => (int) $certificate->getId(),
            'score' => $certificate->getScoreCertificate(),
            'issuedAt' => $certificate->getCreatedAt()->format(DATE_ATOM),
            'publish' => $certificate->getPublish(),
            'viewUrl' => '' !== $hash ? '/certificates/'.rawurlencode($hash).'.html' : '',
            'downloadUrl' => '' !== $hash && !$hideDownload
                ? '/certificates/'.rawurlencode($hash).'.pdf'
                : '',
        ];
    }

    private function meetsEvaluationMinimumScores(GradebookCategory $category, User $user): bool
    {
        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation || null === $evaluation->getMinScore()) {
                continue;
            }

            $result = $this->scoreCalculator->calculateEvaluation($evaluation, $user);
            if (null === $result['score'] || (float) $result['score'] < (float) $evaluation->getMinScore()) {
                return false;
            }
        }

        return true;
    }

    private function getTemplateHtml(GradebookCategory $category): string
    {
        $document = $category->getDocument();
        if ($document instanceof CDocument && null !== $document->getResourceNode()) {
            try {
                $html = $this->documentRepository->getResourceFileContent($document);
                if ('' !== trim($html)) {
                    return $html;
                }
            } catch (Throwable $exception) {
                $this->logger->warning('Unable to read the Gradebook certificate document.', [
                    'categoryId' => (int) $category->getId(),
                    'documentId' => (int) $document->getIid(),
                    'exception' => $exception,
                ]);
            }
        }

        $fallback = $this->projectDir.'/public/main/gradebook/certificate_template/template.html';
        if (!is_file($fallback)) {
            return '';
        }

        $html = file_get_contents($fallback);
        if (false === $html) {
            return '';
        }

        return str_replace('{IMG_PATH}', '/main/gradebook/certificate_template/', $html);
    }

    private function renderTemplate(
        string $template,
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
        float $score,
        DateTimeInterface $issuedAt,
        string $viewUrl,
    ): string {
        $teacher = $this->resolveTeacher($category, $course, $session);
        $scoreText = rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');
        $escapedViewUrl = htmlspecialchars($viewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $replacements = [
            '((user_firstname))' => $this->escape((string) $user->getFirstname()),
            '((user_lastname))' => $this->escape((string) $user->getLastname()),
            '((user_username))' => $this->escape($user->getUsername()),
            '((gradebook_institution))' => $this->escape($this->getSettingString('platform.institution')),
            '((gradebook_sitename))' => $this->escape($this->getSettingString('platform.site_name')),
            '((teacher_firstname))' => $this->escape((string) ($teacher?->getFirstname() ?? '')),
            '((teacher_lastname))' => $this->escape((string) ($teacher?->getLastname() ?? '')),
            '((official_code))' => $this->escape((string) ($user->getOfficialCode() ?? '')),
            '((date_certificate))' => $this->escape($issuedAt->format('Y-m-d H:i')),
            '((date_certificate_no_time))' => $this->escape($issuedAt->format('Y-m-d')),
            '((course_code))' => $this->escape((string) $course->getCode()),
            '((course_title))' => $this->escape((string) $course->getTitle()),
            '((gradebook_grade))' => $this->escape($scoreText.'%'),
            '((gradebook_grade_score))' => $this->escape($scoreText),
            '((gradebook_grade_percentage))' => $this->escape($scoreText.'%'),
            '((certificate_link))' => $escapedViewUrl,
            '((certificate_link_html))' => '' !== $viewUrl
                ? '<a href="'.$escapedViewUrl.'" target="_blank" rel="noopener noreferrer">'
                    .$this->escape($this->translator->trans('Online link to certificate'))
                    .'</a>'
                : '',
            '((certificate_barcode))' => '',
            '((external_style))' => '',
            '((time_in_course))' => '',
            '((time_in_course_in_all_sessions))' => '',
            '((start_date_and_end_date))' => '',
            '((course_objectives))' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function requiresLegacyTemplateCompatibility(string $template): bool
    {
        if (preg_match_all('/\(\([A-Za-z0-9_]+\)\)/', $template, $matches) < 1) {
            return false;
        }

        $modernTags = [
            '((user_firstname))',
            '((user_lastname))',
            '((user_username))',
            '((gradebook_institution))',
            '((gradebook_sitename))',
            '((teacher_firstname))',
            '((teacher_lastname))',
            '((official_code))',
            '((date_certificate))',
            '((date_certificate_no_time))',
            '((course_code))',
            '((course_title))',
            '((gradebook_grade))',
            '((gradebook_grade_score))',
            '((gradebook_grade_percentage))',
            '((certificate_link))',
            '((certificate_link_html))',
            '((certificate_barcode))',
            '((external_style))',
        ];

        foreach ($matches[0] as $tag) {
            if (!\in_array($tag, $modernTags, true)) {
                return true;
            }
        }

        return false;
    }

    private function resolveTeacher(
        GradebookCategory $category,
        Course $course,
        ?Session $session,
    ): ?User {
        if ($session instanceof Session) {
            $relation = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy(
                [
                    'course' => $course,
                    'session' => $session,
                    'status' => Session::COURSE_COACH,
                ],
                ['id' => 'ASC'],
            );
            if ($relation instanceof SessionRelCourseRelUser) {
                return $relation->getUser();
            }
        }

        $relation = $this->entityManager->getRepository(CourseRelUser::class)->findOneBy(
            [
                'course' => $course,
                'status' => CourseRelUser::TEACHER,
            ],
            ['id' => 'ASC'],
        );
        if ($relation instanceof CourseRelUser) {
            return $relation->getUser();
        }

        return $category->getUser();
    }

    private function isCourseSettingEnabled(Course $course, string $variable): bool
    {
        $settings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $course->getId(),
                'variable' => $variable,
            ],
            ['iid' => 'ASC'],
        );
        foreach ($settings as $setting) {
            if (!$setting instanceof CCourseSetting || null === $setting->getValue()) {
                continue;
            }

            $value = trim($setting->getValue());
            if ('-1' === $value) {
                continue;
            }

            return '1' === $value || 'true' === strtolower($value);
        }

        return false;
    }

    private function getSettingString(string $name): string
    {
        $value = $this->settingsManager->getSetting($name, true);

        return \is_scalar($value) ? trim((string) $value) : '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
