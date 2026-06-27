<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathRuntimeItemInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathRuntimeItemInput, void> */
final readonly class LearningPathRuntimeItemProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    private const EXTERNALLY_COMPLETED_TYPES = ['quiz', 'hotpotatoes', 'sco', 'au'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LearningPathRuntimeProvider $runtimeProvider,
        private LearningPathRuntimeProgressManager $progressManager,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof LearningPathRuntimeItemInput) {
            throw new BadRequestHttpException('Learning path runtime item data is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId <= 0 || $data->itemId <= 0) {
            throw new BadRequestHttpException('Invalid learning path runtime item identifier.');
        }

        $runtime = $this->runtimeProvider->provide(
            $operation,
            ['lpId' => $lpId],
            [...$context, 'runtime_item_id' => $data->itemId],
        );
        if (!$runtime->runtimeSupported) {
            throw new BadRequestHttpException('This learning path runtime is not supported by the Vue player yet.');
        }
        if ($runtime->currentItemId !== $data->itemId) {
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
        $item = $this->lpItemRepository->find($data->itemId);
        $user = $this->security->getUser();

        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }
        if (!$item instanceof CLpItem || (int) $item->getLp()->getIid() !== $lpId || 'dir' === $item->getItemType()) {
            throw new NotFoundHttpException('Learning path item not found.');
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

        $newView = false;
        if (!$view instanceof CLpView) {
            $view = (new CLpView())
                ->setLp($lp)
                ->setCourse($course)
                ->setSession($session)
                ->setUser($user)
                ->setViewCount(1)
                ->setLastItem($data->itemId)
                ->setProgress(0)
            ;
            $this->entityManager->persist($view);
            $this->entityManager->flush();

            $this->progressManager->initializeItemViews($lp, $view);
            $this->entityManager->flush();
            $newView = true;
        }

        $previousItemId = $newView ? 0 : (int) $view->getLastItem();
        if ($previousItemId > 0) {
            $this->progressManager->recordElapsedTime($view, $previousItemId, false);
        }

        $itemView = $this->progressManager->findLatestItemView($view, $item);
        $itemStatus = $itemView instanceof CLpItemView ? strtolower(trim($itemView->getStatus())) : '';
        if ($itemView instanceof CLpItemView
            && $data->allowNewAttempt
            && !$lp->getPreventReinit()
            && !\in_array($itemStatus, ['not attempted', 'incomplete'], true)
        ) {
            $itemView = null;
        }

        if (!$itemView instanceof CLpItemView) {
            $itemView = (new CLpItemView())
                ->setItem($item)
                ->setView($view)
                ->setViewCount($this->progressManager->getNextItemAttempt($view, $item))
                ->setStartTime(time())
                ->setTotalTime(0)
                ->setScore(0.0)
                ->setStatus('not attempted')
            ;

            $maxScore = $item->getMaxScore();
            if (null !== $maxScore && (float) $maxScore > 0.0) {
                $itemView->setMaxScore((string) $maxScore);
            }

            $this->entityManager->persist($itemView);
        } else {
            $itemView->setStartTime(time());
        }

        if ($this->shouldCompleteWhenOpened($item)) {
            $itemView->setStatus('completed');
        }

        $view->setLastItem($data->itemId);
        $this->entityManager->flush();
        $this->progressManager->synchronize($lp, $view);
    }

    private function shouldCompleteWhenOpened(CLpItem $item): bool
    {
        return !\in_array(strtolower(trim($item->getItemType())), self::EXTERNALLY_COMPLETED_TYPES, true);
    }
}
