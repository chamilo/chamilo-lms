<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use PDF;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;

readonly class ExportCLinksAction
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function __invoke(
        Request $request,
        CLinkRepository $repo,
        CLinkCategoryRepository $repoCategory,
        EntityManager $em,
    ): Response {
        $format = (string) $request->get('format');
        $cid = (int) $request->request->get('cid', 0);
        $sid = (int) $request->request->get('sid', 0);

        if (!\in_array($format, ['pdf'], true)) {
            throw new BadRequestHttpException('Invalid export format.');
        }

        $course = null;
        $session = null;

        if ($cid > 0) {
            $course = $em->find(Course::class, $cid);
        }
        if ($sid > 0) {
            $session = $em->find(Session::class, $sid);
        }

        if (!$course) {
            throw new BadRequestHttpException('Course not found.');
        }

        // Links without category
        $qbNoCat = $repo->getResourcesByCourse($course, $session, null, null, true, true);
        $qbNoCat->andWhere('resource.category = 0 OR resource.category IS NULL');

        /** @var CLink[] $linksWithoutCategory */
        $linksWithoutCategory = $qbNoCat->getQuery()->getResult();

        // Categories
        $qbCat = $repoCategory->getResourcesByCourse($course, $session, null, null, true, true);

        /** @var CLinkCategory[] $categories */
        $categories = $qbCat->getQuery()->getResult();

        // Build a map categoryId => links[]
        $categoryLinks = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category->getIid();
            $qbLinks = $repo->getResourcesByCourse($course, $session);
            $qbLinks->andWhere('resource.category = '.$categoryId);

            /** @var CLink[] $links */
            $links = $qbLinks->getQuery()->getResult();
            $categoryLinks[$categoryId] = $links;
        }

        $exportFilePath = $this->generatePdfFile(
            $course,
            $session,
            $linksWithoutCategory,
            $categories,
            $categoryLinks
        );

        $response = new BinaryFileResponse(new File($exportFilePath));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $response->getFile()->getFilename()
        );

        return $response;
    }

    private function generatePdfFile(
        Course $course,
        ?Session $session,
        array $linksWithoutCategory,
        array $categories,
        array $categoryLinks
    ): string {
        $title = $this->translator->trans('Links');

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { font-size: 18px; margin-bottom: 6px; }
            h2 { font-size: 14px; margin: 14px 0 6px; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
            th { background: #f4f4f4; }
            .muted { color: #666; font-size: 11px; margin-bottom: 10px; }
        </style>';

        $html .= '<h1>'.$title.'</h1>';

        $meta = $course->getCode();
        if ($session) {
            $meta .= ' / '.$this->translator->trans('Session').' #'.$session->getId();
        }
        $html .= '<div class="muted">'.$meta.'</div>';

        // General
        $html .= '<h2>'.$this->translator->trans('General').'</h2>';
        $html .= $this->renderLinksTable($linksWithoutCategory);

        // Categories
        foreach ($categories as $cat) {
            $catId = (int) $cat->getIid();
            $html .= '<h2>'.$cat->getTitle().'</h2>';

            $desc = (string) $cat->getDescription();
            if (!empty($desc)) {
                $html .= '<div class="muted">'.htmlspecialchars($desc, ENT_QUOTES).'</div>';
            }

            $links = $categoryLinks[$catId] ?? [];
            $html .= $this->renderLinksTable($links);
        }

        $fileBase = 'links_course_'.$course->getCode();

        return (new PDF())
            ->content_to_pdf(
                $html,
                null,
                $fileBase,
                $course->getCode(),
                'F',
                false,
                null,
                false,
                true
            )
        ;
    }

    /**
     * @param CLink[] $links
     */
    private function renderLinksTable(array $links): string
    {
        if (empty($links)) {
            return '<div class="muted">'.$this->translator->trans('There are no links in this category').'</div>';
        }

        $html = '<table>';
        $html .= '<tr>';
        $html .= '<th style="width: 28%;">'.$this->translator->trans('Title').'</th>';
        $html .= '<th style="width: 42%;">'.$this->translator->trans('URL').'</th>';
        $html .= '<th style="width: 30%;">'.$this->translator->trans('Description').'</th>';
        $html .= '</tr>';

        foreach ($links as $link) {
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars((string) $link->getTitle(), ENT_QUOTES).'</td>';
            $html .= '<td>'.htmlspecialchars((string) $link->getUrl(), ENT_QUOTES).'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($link->getDescription() ?? ''), ENT_QUOTES).'</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
