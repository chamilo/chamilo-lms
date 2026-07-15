<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderQuickTestInput;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathQuickTestService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathBuilderQuickTestInput, LearningPathBuilderQuickTestInput> */
final readonly class LearningPathQuickTestProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LockFactory $lockFactory,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CDocumentRepository $documentRepository,
        private LearningPathQuickTestService $quickTestService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): LearningPathBuilderQuickTestInput {
        if (!$data instanceof LearningPathBuilderQuickTestInput) {
            throw new BadRequestHttpException('Invalid quick-test payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $lp = $this->lpRepository->find($data->lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }
        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);

        $sourceItemId = (int) ($uriVariables['itemId'] ?? 0);
        $sourceItem = $this->lpItemRepository->find($sourceItemId);
        if (!$sourceItem instanceof CLpItem || $sourceItem->getLp()->getIid() !== $lp->getIid()) {
            throw new NotFoundHttpException('Learning path document item not found.');
        }
        if ('document' !== $sourceItem->getItemType()) {
            throw new BadRequestHttpException('Quick tests can only be generated from an HTML document.');
        }

        $documentId = ctype_digit((string) $sourceItem->getPath()) ? (int) $sourceItem->getPath() : 0;
        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof CDocument) {
            throw new NotFoundHttpException('Document not found.');
        }
        if (!$this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink) {
            throw new NotFoundHttpException('The document is not linked to the current context.');
        }
        if (!$document->getResourceNode()?->hasEditableTextContent()) {
            throw new BadRequestHttpException('The selected document is not an editable HTML document.');
        }

        $root = $this->lpItemRepository->getRootItem((int) $lp->getIid());
        if (!$root instanceof CLpItem) {
            throw new NotFoundHttpException('Learning path root item not found.');
        }

        $lock = $this->lockFactory->createLock('lp_quick_test_'.$sourceItemId, 300.0);
        if (!$lock->acquire()) {
            throw new ConflictHttpException('A quick test is already being generated for this document.');
        }

        try {
            $generated = $this->quickTestService->createExercise(
                $course,
                $document,
                $sourceItem->getTitle(),
                $data->provider,
            );
            $quiz = $this->entityManager->getRepository(CQuiz::class)->find($generated['exerciseId']);
            if (!$quiz instanceof CQuiz) {
                throw new NotFoundHttpException('The generated exercise could not be loaded.');
            }

            $quizLink = $quiz->getResourceNode()?->getResourceLinkByContext($course, $session, $group);
            if (!$quizLink instanceof ResourceLink && null !== $group) {
                $quiz->addCourseLink($course, $session, $group);
                $this->entityManager->persist($quiz);
                $this->entityManager->flush();
                $quizLink = $quiz->getResourceNode()?->getResourceLinkByContext($course, $session, $group);
            }
            if (!$quizLink instanceof ResourceLink) {
                $quizLink = $this->getContextResourceLink($quiz, $course, $session, $group);
            }
            if (!$quizLink instanceof ResourceLink) {
                throw new BadRequestHttpException('The generated exercise is not linked to the current context.');
            }
            $quizLink->setVisibility(ResourceLink::VISIBILITY_DRAFT);
            $this->entityManager->persist($quizLink);

            $sourceOrder = (int) $sourceItem->getDisplayOrder();

            /** @var CLpItem[] $followingItems */
            $followingItems = $this->lpItemRepository->createQueryBuilder('item')
                ->andWhere('item.lp = :lpId')
                ->andWhere('item.itemType != :rootType')
                ->andWhere('item.displayOrder > :sourceOrder')
                ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
                ->setParameter('rootType', 'root', Types::STRING)
                ->setParameter('sourceOrder', $sourceOrder, Types::INTEGER)
                ->orderBy('item.displayOrder', 'DESC')
                ->getQuery()
                ->getResult()
            ;
            foreach ($followingItems as $followingItem) {
                $followingItem->setDisplayOrder((int) $followingItem->getDisplayOrder() + 1);
                $this->entityManager->persist($followingItem);
            }

            $parent = $sourceItem->getParent();
            if (!$parent instanceof CLpItem) {
                $parent = $root;
            }

            $quickTestItem = (new CLpItem())
                ->setLp($lp)
                ->setRoot($root)
                ->setParent($parent)
                ->setItemType('quiz')
                ->setTitle($generated['title'])
                ->setDescription('')
                ->setPath((string) $generated['exerciseId'])
                ->setRef('')
                ->setPrerequisite('')
                ->setMaxTimeAllowed('0')
                ->setMaxScore((float) $quiz->getMaxScore())
                ->setDisplayOrder($sourceOrder + 1)
                ->setPreviousItemId(null)
                ->setNextItemId(null)
            ;

            $lp->setModifiedOn(new DateTime());
            $this->entityManager->persist($quickTestItem);
            $this->entityManager->persist($lp);
            $this->entityManager->flush();
            $this->lpItemRepository->recoverNode($root, 'displayOrder');
            $this->entityManager->flush();

            $data->itemId = (int) $quickTestItem->getIid();
            $data->exerciseId = (int) $quiz->getIid();
            $data->title = $generated['title'];
            $data->provider = $generated['provider'];
            $data->created = true;

            return $data;
        } finally {
            $lock->release();
        }
    }
}
