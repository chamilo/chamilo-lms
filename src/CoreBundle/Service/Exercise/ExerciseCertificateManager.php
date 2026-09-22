<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CQuiz;
use RuntimeException;

/**
 * Generates, or looks up, the learner's Gradebook certificate from an exercise attempt, when the
 * exercise is the sole certifying component of its Gradebook category.
 *
 * Restores, for the Vue exercise runtime, what the legacy quiz result pages already did via
 * ExerciseLib::generateAndShowCertificateBlock() (public/main/inc/lib/exercise.lib.php): generate
 * the certificate as a side effect of the learner passing the exercise, so a teacher does not have
 * to click "Generate" in Gradebook > Certificates first. Everywhere else (multi-component
 * categories, Learning Path completion) keeps its own existing trigger — see
 * LearningPathFinalItemManager::completeForLearner() and GradebookCertificateActionProcessor.
 */
final readonly class ExerciseCertificateManager
{
    public function __construct(
        private GradebookLinkManager $linkManager,
        private GradebookCertificateGenerator $certificateGenerator,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * Called once, right after the exercise attempt's final score is persisted. Generates the
     * certificate when eligible; never throws — the exercise finish itself must still succeed
     * when the certificate can't be issued (no template, category not eligible, etc.).
     *
     * @return array<string, mixed> the normalized certificate summary, or [] when not applicable
     */
    public function generateOnFinish(CQuiz $quiz, Course $course, ?Session $session, User $user): array
    {
        $category = $this->resolveSoleValidationCategory($quiz, $course, $session);
        if (!$category instanceof GradebookCategory) {
            return [];
        }

        try {
            $certificate = $this->certificateGenerator->generate($category, $user, $course, $session);
        } catch (RuntimeException) {
            // Not eligible yet (score, "generate certificates" flag, or template) — nothing to show.
            return [];
        }

        return $this->certificateGenerator->normalizeCertificate($certificate, false);
    }

    /**
     * Read-only lookup for the result page: the certificate is expected to already exist from
     * generateOnFinish(), so this never generates one.
     *
     * @return array<string, mixed> the normalized certificate summary, or [] when there is none
     */
    public function getExistingCertificate(CQuiz $quiz, Course $course, ?Session $session, User $user): array
    {
        $category = $this->resolveSoleValidationCategory($quiz, $course, $session);
        if (!$category instanceof GradebookCategory) {
            return [];
        }

        return $this->certificateGenerator->getCertificateSummary($category, $user, false) ?? [];
    }

    /**
     * The Gradebook category this exercise alone certifies — null unless the platform setting is
     * on and this exercise is the category's only Gradebook link, so passing any OTHER component
     * (or a category this exercise merely contributes to) never auto-issues a certificate here.
     */
    private function resolveSoleValidationCategory(CQuiz $quiz, Course $course, ?Session $session): ?GradebookCategory
    {
        if ('true' !== $this->settingsManager->getSetting('exercise.quiz_generate_certificate_ending', true)) {
            return null;
        }

        $link = $this->linkManager->findLink(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_EXERCISE,
            (int) $quiz->getIid(),
        );
        if (!$link instanceof GradebookLink) {
            return null;
        }

        $category = $link->getCategory();
        if (1 !== \count($category->getLinks())) {
            return null;
        }

        return $category;
    }
}
