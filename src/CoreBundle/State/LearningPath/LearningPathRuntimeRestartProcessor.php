<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathRuntimeRestartInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathRuntimeRestartInput, void> */
final readonly class LearningPathRuntimeRestartProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LearningPathRuntimeProvider $runtimeProvider,
        private LearningPathRuntimeProgressManager $progressManager,
        private CLpRepository $lpRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof LearningPathRuntimeRestartInput) {
            throw new BadRequestHttpException('Learning path runtime restart data is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path identifier.');
        }

        $runtime = $this->runtimeProvider->provide($operation, ['lpId' => $lpId], $context);
        if (!$runtime->runtimeSupported) {
            throw new BadRequestHttpException('This learning path runtime is not supported by the Vue player yet.');
        }

        if ($this->canManageLearningPaths($this->security)
            && !$this->isStudentViewRequest($this->requestStack)
        ) {
            throw new AccessDeniedHttpException('Author view does not create learning path attempts.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $lp = $this->lpRepository->find($lpId);
        $user = $this->security->getUser();

        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $this->entityManager->wrapInTransaction(function () use ($lp, $course, $session, $user): void {
            $this->entityManager->lock($lp, LockMode::PESSIMISTIC_WRITE);

            /** @var CLpView|null $latestView */
            $latestView = $this->entityManager->getRepository(CLpView::class)->findOneBy(
                [
                    'lp' => $lp,
                    'course' => $course,
                    'session' => $session,
                    'user' => $user,
                ],
                [
                    'viewCount' => 'DESC',
                    'iid' => 'DESC',
                ],
            );

            $currentAttempt = max(0, (int) ($latestView?->getViewCount() ?? 0));
            $maxAttempts = max(0, $lp->getMaxAttempts());
            if ($maxAttempts > 0 && $currentAttempt >= $maxAttempts) {
                throw new ConflictHttpException('The maximum number of learning path attempts has been reached.');
            }

            if ($latestView instanceof CLpView) {
                $this->progressManager->recordElapsedTime($latestView, null, false);
                $this->progressManager->synchronize($lp, $latestView);
            }

            $newView = (new CLpView())
                ->setLp($lp)
                ->setCourse($course)
                ->setSession($session)
                ->setUser($user)
                ->setViewCount($currentAttempt + 1)
                ->setLastItem(0)
                ->setProgress(0)
            ;

            $this->entityManager->persist($newView);
            $this->entityManager->flush();
            $this->progressManager->initializeItemViews($lp, $newView);
            $this->entityManager->flush();
        });
    }
}
