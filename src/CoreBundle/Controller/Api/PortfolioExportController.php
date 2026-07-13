<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemDownloadedEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Portfolio\PortfolioAccessHelperTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

#[IsGranted('ROLE_USER')]
final class PortfolioExportController extends AbstractController
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PortfolioRepository $portfolioRepository,
        private readonly PortfolioCommentRepository $commentRepository,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly Security $security,
        private readonly UserHelper $userHelper,
        private readonly SettingsManager $settingsManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[Route('/api/portfolio/export.pdf', name: 'api_portfolio_export_pdf', methods: ['GET'])]
    public function pdf(Request $request): Response
    {
        [$owner, $course, $session, $items, $comments] = $this->resolveExport($request);
        $html = $this->buildExportHtml($owner, $course, $session, $items, $comments);

        $pdf = new Mpdf([
            'tempDir' => sys_get_temp_dir(),
            'mode' => 'utf-8',
            'format' => 'A4',
        ]);
        $pdf->WriteHTML($html);
        $content = $pdf->Output('', Destination::STRING_RETURN);
        $filename = $this->exportBaseName($owner, $course).'.pdf';

        $this->dispatchDownloaded($owner);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        ));
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    #[Route('/api/portfolio/export.zip', name: 'api_portfolio_export_zip', methods: ['GET'])]
    public function zip(Request $request): BinaryFileResponse
    {
        [$owner, $course, $session, $items, $comments] = $this->resolveExport($request);
        $tempPath = tempnam(sys_get_temp_dir(), 'chamilo_portfolio_');
        if (false === $tempPath) {
            throw new RuntimeException('The Portfolio ZIP file could not be created.');
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($tempPath, ZipArchive::OVERWRITE)) {
            @unlink($tempPath);

            throw new RuntimeException('The Portfolio ZIP archive could not be opened.');
        }

        try {
            $zip->addFromString('index.html', $this->buildExportHtml($owner, $course, $session, $items, $comments));

            foreach ($items as $item) {
                $directory = \sprintf('items/%d', (int) $item->getId());
                $zip->addFromString($directory.'/item.html', $this->buildItemHtml($item));
                $this->addAttachments($zip, $directory.'/attachments', $item->getResourceNode()->getResourceFiles()->toArray());
            }

            foreach ($comments as $comment) {
                $directory = \sprintf('comments/%d', (int) $comment->getId());
                $zip->addFromString($directory.'/comment.html', $this->buildCommentHtml($comment));
                $this->addAttachments($zip, $directory.'/attachments', $comment->getResourceNode()->getResourceFiles()->toArray());
            }
        } finally {
            $zip->close();
        }

        $this->dispatchDownloaded($owner);

        $response = new BinaryFileResponse($tempPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->exportBaseName($owner, $course).'.zip',
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return array{0: User, 1: Course|null, 2: Session|null, 3: array<int, Portfolio>, 4: array<int, PortfolioComment>}
     */
    private function resolveExport(Request $request): array
    {
        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);
        if ($course instanceof Course && !$this->canReadPortfolioCourse(
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to export Portfolio in this context.');
        }

        $owner = $this->getPortfolioRequestedUser($this->entityManager, $request, $currentUser);
        if ($course instanceof Course
            && $owner->getId() !== $currentUser->getId()
            && !$this->isPortfolioCourseUser($owner, $course, $session)
        ) {
            throw new AccessDeniedHttpException('The requested Portfolio owner is outside the current course context.');
        }
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);
        if ($owner->getId() !== $currentUser->getId() && !$canManage && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('You are not allowed to export another user Portfolio.');
        }

        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );

        $qb = $this->portfolioRepository->createQueryBuilder('item')
            ->select('DISTINCT item', 'node', 'links', 'files', 'category')
            ->innerJoin('item.resourceNode', 'node')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->leftJoin('item.category', 'category')
            ->andWhere('node.creator = :ownerId')
            ->setParameter('ownerId', (int) $owner->getId(), Types::INTEGER)
            ->orderBy('node.createdAt', 'DESC')
        ;
        if ($course instanceof Course) {
            $qb
                ->andWhere('links.course = :courseId')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ;
        }

        /** @var array<int, Portfolio> $candidateItems */
        $candidateItems = $qb->getQuery()->getResult();
        $items = array_values(array_filter(
            $candidateItems,
            fn (Portfolio $item): bool => $this->canViewPortfolioItem(
                $item,
                $currentUser,
                $course,
                $session,
                $showBasePosts,
                $advancedSharing,
                $canManage,
            ),
        ));
        $commentsQuery = $this->commentRepository->createQueryBuilder('comment')
            ->select('DISTINCT comment', 'node', 'links', 'files', 'item', 'itemNode', 'itemLinks')
            ->innerJoin('comment.resourceNode', 'node')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->innerJoin('comment.item', 'item')
            ->innerJoin('item.resourceNode', 'itemNode')
            ->leftJoin('itemNode.resourceLinks', 'itemLinks')
            ->andWhere('node.creator = :ownerId')
            ->setParameter('ownerId', (int) $owner->getId(), Types::INTEGER)
            ->orderBy('comment.date', 'DESC')
        ;
        if ($course instanceof Course) {
            $commentsQuery
                ->andWhere('itemLinks.course = :commentCourseId')
                ->setParameter('commentCourseId', (int) $course->getId(), Types::INTEGER)
            ;
            if ($session instanceof Session) {
                $commentsQuery
                    ->andWhere('(itemLinks.session = :commentSessionId OR itemLinks.session IS NULL)')
                    ->setParameter('commentSessionId', (int) $session->getId(), Types::INTEGER)
                ;
            } else {
                $commentsQuery->andWhere('itemLinks.session IS NULL');
            }
        }

        /** @var array<int, PortfolioComment> $candidateComments */
        $candidateComments = $commentsQuery->getQuery()->getResult();
        $comments = array_values(array_filter(
            $candidateComments,
            fn (PortfolioComment $comment): bool => $this->canViewPortfolioItem(
                $comment->getItem(),
                $currentUser,
                $course,
                $session,
                $showBasePosts,
                $advancedSharing,
                $canManage,
            ) && $this->canViewPortfolioComment(
                $comment,
                $currentUser,
                $course,
                $session,
                $advancedSharing,
                $showBasePosts,
            ),
        ));

        return [$owner, $course, $session, $items, $comments];
    }

    /**
     * @param array<int, Portfolio>        $items
     * @param array<int, PortfolioComment> $comments
     */
    private function buildExportHtml(
        User $owner,
        ?Course $course,
        ?Session $session,
        array $items,
        array $comments,
    ): string {
        $title = $this->escape($owner->getFullName()).' — Portfolio';
        $context = '';
        if ($course instanceof Course) {
            $context = '<p><strong>Course:</strong> '.$this->escape($course->getTitle());
            if ($session instanceof Session) {
                $context .= ' — '.$this->escape($session->getTitle());
            }
            $context .= '</p>';
        }

        $body = '<h1>'.$title.'</h1>'.$context;
        $body .= '<h2>Portfolio items ('.\count($items).')</h2>';
        foreach ($items as $item) {
            $body .= $this->buildItemHtml($item);
        }
        if ([] === $items) {
            $body .= '<p>No Portfolio items.</p>';
        }

        $body .= '<h2>Comments made ('.\count($comments).')</h2>';
        foreach ($comments as $comment) {
            $body .= $this->buildCommentHtml($comment);
        }
        if ([] === $comments) {
            $body .= '<p>No Portfolio comments.</p>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><title>'.$title.'</title>'
            .'<style>body{font-family:sans-serif;color:#222}article{border:1px solid #ddd;padding:14px;margin:0 0 14px}'
            .'h1,h2,h3{color:#333}.meta{color:#666;font-size:12px}.content{margin-top:10px}</style></head><body>'
            .$body.'</body></html>';
    }

    private function buildItemHtml(Portfolio $item): string
    {
        $node = $item->getResourceNode();
        $category = $item->getCategory()?->getTitle();
        $metadata = $node->getCreatedAt()->format('Y-m-d H:i:s');
        if (null !== $category && '' !== $category) {
            $metadata .= ' · '.$this->escape($category);
        }

        return '<article><h3>'.$this->escape(strip_tags($item->getTitle())).'</h3>'
            .'<div class="meta">'.$metadata.'</div>'
            .'<div class="content">'.$this->sanitizePortfolioHtml($item->getContent()).'</div></article>';
    }

    private function buildCommentHtml(PortfolioComment $comment): string
    {
        return '<article><h3>'.$this->escape(strip_tags($comment->getItem()->getTitle())).'</h3>'
            .'<div class="meta">'.$comment->getDate()->format('Y-m-d H:i:s').'</div>'
            .'<div class="content">'.$this->sanitizePortfolioHtml($comment->getContent()).'</div></article>';
    }

    /**
     * @param array<int, mixed> $attachments
     */
    private function addAttachments(ZipArchive $zip, string $directory, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (!$attachment instanceof ResourceFile) {
                continue;
            }

            try {
                $storagePath = $this->resourceNodeRepository->getFilename($attachment);
                if (null === $storagePath || !$this->resourceNodeRepository->getFileSystem()->fileExists($storagePath)) {
                    continue;
                }
                $content = $this->resourceNodeRepository->getFileSystem()->read($storagePath);
                $filename = $this->safeFilename((string) ($attachment->getOriginalName() ?: $attachment->getTitle()));
                $zip->addFromString($directory.'/'.$filename, $content);
            } catch (Throwable) {
                continue;
            }
        }
    }

    private function exportBaseName(User $owner, ?Course $course): string
    {
        $name = $owner->getFullName().($course instanceof Course ? '_'.$course->getCode() : '').'_Portfolio';

        return $this->safeFilename($name);
    }

    private function safeFilename(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/u', '_', trim($value)) ?? 'portfolio';

        return '' !== $value ? trim($value, '._-') : 'portfolio';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function dispatchDownloaded(User $owner): void
    {
        $this->eventDispatcher->dispatch(
            new PortfolioItemDownloadedEvent(['owner' => $owner]),
            Events::PORTFOLIO_DOWNLOADED,
        );
    }
}
