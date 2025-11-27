<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Export;
use PDF;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;

readonly class ExportCGlossaryAction
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @throws Exception|NotSupported|ORMException|OptimisticLockException|TransactionRequiredException
     */
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        EntityManager $em,
        TranslatorInterface $translator
    ): Response {
        $format = $request->get('format');
        $cid = $request->request->get('cid');
        $sid = $request->request->get('sid');

        if (!\in_array($format, ['csv', 'xls', 'pdf'], true)) {
            throw new BadRequestHttpException('Invalid export format');
        }

        $course = null;
        $session = null;
        if (0 !== $cid) {
            $course = $em->find(Course::class, $cid);
        }
        if (0 !== $sid) {
            $session = $em->find(Session::class, $sid);
        }

        $qb = $repo->getResourcesByCourse($course, $session);
        $glossaryItems = $qb->getQuery()->getResult();

        $exportFilePath = $this->generateExportFile(
            $glossaryItems,
            $format,
            $course,
        );

        $response = new BinaryFileResponse(
            new File($exportFilePath)
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $response->getFile()->getFilename()
        );

        return $response;
    }

    /**
     * @throws NotSupported|Exception
     */
    private function generateExportFile(
        array $glossaryItems,
        string $format,
        ?Course $course,
    ): string {
        if ('pdf' === $format) {
            return $this->generatePdfFile($glossaryItems, $course);
        }

        $list = [];
        $list[] = ['term', 'definition'];

        $allowStrip = 'true' === $this->settingsManager->getSetting('glossary.allow_remove_tags_in_glossary_export');

        foreach ($glossaryItems as $item) {
            $definition = $item->getDescription();

            if ($allowStrip) {
                $definition = htmlspecialchars_decode(strip_tags($definition), ENT_QUOTES);
            }

            $list[] = [$item->getTitle(), $definition];
        }

        return match ($format) {
            'csv' => $this->generateCsvFile($list, $course),
            'xls' => $this->generateExcelFile($list, $course),
        };
    }

    private function generateCsvFile(array $glossaryItems, Course $course): string
    {
        return Export::arrayToCsv($glossaryItems, 'glossary_course_'.$course->getCode(), true);
    }

    private function generateExcelFile(array $glossaryItems, Course $course): string
    {
        return Export::arrayToXls($glossaryItems, 'glossary_course_'.$course->getCode(), true);
    }

    private function generatePdfFile(array $glossaryItems, Course $course): string
    {
        $html = '<h1>'.$this->translator->trans('Glossary').'</h1>';
        $html .= '<table>';
        $html .= '<tr><th>'.$this->translator->trans('Term').'</th><th>'.$this->translator->trans('Term definition').'</th></tr>';

        /** @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $html .= '<tr>';
            $html .= '<td>'.$item->getTitle().'</td>';
            $html .= '<td>'.$item->getDescription().'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return (new PDF())
            ->content_to_pdf(
                $html,
                null,
                get_lang('Glossary').'_'.$course->getCode(),
                $course->getCode(),
                'F',
                false,
                null,
                false,
                true
            )
        ;
    }
}
