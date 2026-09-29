<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupManager;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const ENT_QUOTES;
use const PHP_SESSION_ACTIVE;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseGroupExportController extends AbstractController
{
    public function __construct(
        private readonly CourseGroupManager $manager,
    ) {}

    #[Route(
        '/api/course-groups/export.{format}',
        name: 'api_course_group_export',
        requirements: ['format' => 'csv|xlsx|pdf'],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $format): Response
    {
        $groupId = $request->query->getInt('groupId');
        $rows = $this->manager->getExportData($groupId > 0 ? $groupId : null, true);
        $filename = $groupId > 0 ? 'course-group-'.$groupId : 'course-groups';

        return match ($format) {
            'csv' => $this->exportCsv($rows, $filename.'.csv'),
            'xlsx' => $this->exportXlsx($rows, $filename.'.xlsx'),
            default => $this->exportPdf($rows, $filename.'.pdf'),
        };
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function exportCsv(array $rows, string $filename): StreamedResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $response = new StreamedResponse(static function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            if (false === $stream) {
                return;
            }
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }
            fclose($stream);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function exportXlsx(array $rows, string $filename): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'course_groups_xlsx_');
        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create the export file.');
        }
        (new Xlsx($spreadsheet))->save($temporaryFile);
        $spreadsheet->disconnectWorksheets();

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }
        $response = new BinaryFileResponse($temporaryFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function exportPdf(array $rows, string $filename): Response
    {
        $html = '<h1>'.htmlspecialchars(get_lang('Groups overview'), ENT_QUOTES, 'UTF-8').'</h1>';
        $html .= '<table style="border-collapse:collapse;width:100%;font-size:8pt">';
        foreach ($rows as $index => $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $tag = 0 === $index ? 'th' : 'td';
                $html .= '<'.$tag.' style="border:1px solid #999;padding:4px">'
                    .htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</'.$tag.'>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }
        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'L',
            'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
        ], SafeMpdfHttpClient::container());
        $mpdf->SetTitle(get_lang('Groups overview'));
        $mpdf->WriteHTML($html);

        return new Response(
            $mpdf->Output($filename, Destination::STRING_RETURN),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ],
        );
    }
}
