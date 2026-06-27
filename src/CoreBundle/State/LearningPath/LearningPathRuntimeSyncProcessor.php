<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathRuntimeSyncInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathRuntimeSyncInput, void> */
final readonly class LearningPathRuntimeSyncProcessor implements ProcessorInterface
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
        if (!$data instanceof LearningPathRuntimeSyncInput) {
            throw new BadRequestHttpException('Learning path runtime sync data is required.');
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

        $runtime = $this->runtimeProvider->provide(
            $operation,
            ['lpId' => $lpId],
            $data->itemId > 0 ? [...$context, 'runtime_item_id' => $data->itemId] : $context,
        );
        if ($data->itemId > 0 && $runtime->currentItemId !== $data->itemId) {
            throw new AccessDeniedHttpException('The learning path item is not available.');
        }

        if ($this->canManageLearningPaths($this->security)
            && !$this->isStudentViewRequest($this->requestStack)
        ) {
            return;
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

        /** @var CLpView|null $view */
        $view = $this->entityManager->getRepository(CLpView::class)->findOneBy(
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

        if (!$view instanceof CLpView) {
            return;
        }

        $activeItemId = $data->itemId > 0 ? $data->itemId : (int) $view->getLastItem();
        $this->progressManager->recordElapsedTime($view, $activeItemId, false);
        $this->progressManager->synchronize($lp, $view);
    }
}
