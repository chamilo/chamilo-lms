<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookAchievementAction;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookScoreLog;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Service\Gradebook\GradebookSkillAwarder;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookAchievementAction, GradebookAchievementAction>
 */
final readonly class GradebookAchievementActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_achievement_sync';

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCertificateGenerator $certificateGenerator,
        private GradebookSkillAwarder $skillAwarder,
        private GradebookCertificateRepository $certificateRepository,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookAchievementAction
    {
        if (!$data instanceof GradebookAchievementAction) {
            throw new BadRequestHttpException('A valid Gradebook achievement action is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $resolved = $this->contextResolver->resolve($request);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $learner = $this->contextResolver->getStudentInContext(
            (int) $resolved['user']->getId(),
            $resolved['course'],
            $resolved['session'],
        );
        $categoryId = (int) ($data->categoryId ?? 0);
        $category = $categoryId > 0
            ? $this->contextResolver->getCategoryInGradebook(
                $categoryId,
                $rootCategory,
                $resolved['course'],
                $resolved['session'],
            )
            : $rootCategory;

        if (!$category->getVisible()) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }

        $eligibility = $this->certificateGenerator->getAcademicEligibility(
            $category,
            $learner,
            $resolved['course'],
            $resolved['session'],
        );

        $scoreLog = (new GradebookScoreLog())
            ->setCategory($category)
            ->setUser($learner)
            ->setScore((float) $eligibility['score'])
            ->setRegisteredAt(new DateTime('now', new DateTimeZone('UTC')))
        ;
        $this->entityManager->persist($scoreLog);
        $this->entityManager->flush();

        $response = new GradebookAchievementAction();
        $response->categoryId = (int) $category->getId();
        $response->eligible = $eligibility['eligible'];
        $response->scoreRegistered = true;

        if (!$category->getGenerateCertificates() || (float) $eligibility['minimumScore'] <= 0.0) {
            $response->message = 'The current Gradebook score was registered.';

            return $response;
        }

        if (!$eligibility['eligible']) {
            $response->message = $eligibility['reason'];

            return $response;
        }

        $existing = $this->certificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $learner->getId(),
        );
        if ($existing instanceof GradebookCertificate) {
            $response->certificate = $this->certificateGenerator->getCertificateSummary($category, $learner, false);
            $response->message = 'Gradebook achievements synchronized.';

            return $response;
        }

        if ($this->certificateGenerator->usesCustomCertificate($resolved['course'])) {
            $this->skillAwarder->award($category, $learner, $resolved['course'], $resolved['session']);
            $response->customCertificateFallback = true;
            $response->message = 'CustomCertificate generation remains handled by the plugin workflow.';

            return $response;
        }

        $certificate = $this->certificateGenerator->generate(
            $category,
            $learner,
            $resolved['course'],
            $resolved['session'],
        );
        $response->certificateGenerated = true;
        $response->certificate = $this->certificateGenerator->normalizeCertificate($certificate, false);
        $response->message = 'Gradebook achievements synchronized.';

        return $response;
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
    }
}
