<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\State\CourseUser\CourseUserManager;
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
final class CourseUserExportController extends AbstractController
{
    public function __construct(
        private readonly CourseUserManager $courseUserManager,
    ) {}

    #[Route(
        '/api/course-users/export.{format}',
        name: 'api_course_user_export',
        requirements: ['format' => 'csv|xlsx|pdf'],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $format): Response
    {
        $data = $this->loadAllData($request);
        $rows = $this->buildRows($data);
        $typeLabel = CourseUserManager::TYPE_TEACHER === (int) $data['type'] ? 'teachers' : 'students';
        $filename = 'course-users-'.$typeLabel;

        if ('csv' === $format) {
            return $this->exportCsv($rows, $filename.'.csv');
        }

        if ('xlsx' === $format) {
            return $this->exportXlsx($rows, $filename.'.xlsx');
        }

        return $this->exportPdf($data, $filename.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAllData(Request $request): array
    {
        [$course, $session] = $this->courseUserManager->resolveContext();
        if (!$this->courseUserManager->canManage($course, $session)) {
            throw $this->createAccessDeniedException('You are not allowed to export users in this context.');
        }

        $page = 1;
        $items = [];
        $firstData = [];

        do {
            $query = $request->query->all();
            $query['page'] = $page;
            $query['itemsPerPage'] = 100;
            $pageRequest = $request->duplicate($query);
            $data = $this->courseUserManager->getListData($pageRequest);
            if ([] === $firstData) {
                $firstData = $data;
            }
            $items = array_merge($items, (array) $data['items']);
            $page++;
        } while (\count($items) < (int) $data['totalItems']);

        $firstData['items'] = $items;
        $firstData['totalItems'] = \count($items);

        return $firstData;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<int, string|int>>
     */
    private function buildRows(array $data): array
    {
        $showEmail = !empty($data['showEmail']);
        $extraFields = \is_array($data['extraFields'] ?? null) ? $data['extraFields'] : [];
        $sortByFirstName = api_sort_by_first_name();
        $headers = ['ID'];
        if ($sortByFirstName) {
            $headers[] = get_lang('First name');
            $headers[] = get_lang('Last name');
        } else {
            $headers[] = get_lang('Last name');
            $headers[] = get_lang('First name');
        }
        $headers[] = get_lang('Username');

        if ($showEmail) {
            $headers[] = get_lang('E-mail');
        }

        $headers[] = get_lang('Phone');
        $headers[] = get_lang('Code');
        $headers[] = get_lang('active');
        if (!empty($data['showLegalAgreement'])) {
            $headers[] = get_lang('Legal agreement accepted');
        }
        foreach ($extraFields as $field) {
            $headers[] = (string) ($field['label'] ?? '');
        }

        $rows = [$headers];

        foreach ((array) $data['items'] as $item) {
            $row = [(int) ($item['id'] ?? 0)];
            if ($sortByFirstName) {
                $row[] = (string) ($item['firstname'] ?? '');
                $row[] = (string) ($item['lastname'] ?? '');
            } else {
                $row[] = (string) ($item['lastname'] ?? '');
                $row[] = (string) ($item['firstname'] ?? '');
            }
            $row[] = (string) ($item['username'] ?? '');

            if ($showEmail) {
                $row[] = (string) ($item['email'] ?? '');
            }

            $row[] = (string) ($item['phone'] ?? '');
            $row[] = (string) ($item['officialCode'] ?? '');
            $row[] = !empty($item['active']) ? get_lang('Yes') : get_lang('No');
            if (!empty($data['showLegalAgreement'])) {
                $row[] = !empty($item['legalAgreement']) ? get_lang('Yes') : get_lang('No');
            }
            foreach ($extraFields as $field) {
                $fieldId = (string) ($field['id'] ?? '');
                $row[] = (string) ($item['extraValues'][$fieldId] ?? '');
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string|int>> $rows
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
     * @param array<int, array<int, string|int>> $rows
     */
    private function exportXlsx(array $rows, string $filename): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'course_users_xlsx_');
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
     * @param array<string, mixed> $data
     */
    private function exportPdf(array $data, string $filename): Response
    {
        $westernNameOrder = !isset($data['westernNameOrder']) || !empty($data['westernNameOrder']);
        $extraFields = \is_array($data['extraFields'] ?? null) ? $data['extraFields'] : [];
        $headers = [
            '#',
            get_lang('Picture'),
            get_lang('Code'),
            $westernNameOrder
                ? get_lang('First name').', '.get_lang('Last name')
                : get_lang('Last name').', '.get_lang('First name'),
            get_lang('E-mail'),
            get_lang('Phone'),
        ];
        foreach ($extraFields as $field) {
            $headers[] = (string) ($field['label'] ?? '');
        }

        $html = '<h1>'.htmlspecialchars(get_lang('Users'), ENT_QUOTES, 'UTF-8').'</h1>';
        $html .= '<table style="border-collapse:collapse;width:100%;font-size:9pt">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th style="border:1px solid #999;padding:5px">'.htmlspecialchars(
                $header,
                ENT_QUOTES,
                'UTF-8',
            ).'</th>';
        }
        $html .= '</tr>';

        $counter = 1;
        foreach ((array) ($data['items'] ?? []) as $item) {
            $pictureUrl = (string) ($item['pictureUrl'] ?? '');
            $fullName = $westernNameOrder
                ? trim((string) ($item['firstname'] ?? '').', '.(string) ($item['lastname'] ?? ''), ', ')
                : trim((string) ($item['lastname'] ?? '').', '.(string) ($item['firstname'] ?? ''), ', ');
            $html .= '<tr>';
            $html .= '<td style="border:1px solid #999;padding:5px">'.$counter.'</td>';
            $html .= '<td style="border:1px solid #999;padding:5px">';
            if ('' !== $pictureUrl) {
                $html .= '<img src="'.htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8').'" width="80" />';
            }
            $html .= '</td>';
            $values = [
                (string) ($item['officialCode'] ?? ''),
                $fullName,
                (string) ($item['email'] ?? ''),
                (string) ($item['phone'] ?? ''),
            ];
            foreach ($extraFields as $field) {
                $fieldId = (string) ($field['id'] ?? '');
                $values[] = (string) ($item['extraValues'][$fieldId] ?? '');
            }
            foreach ($values as $value) {
                $html .= '<td style="border:1px solid #999;padding:5px">'.htmlspecialchars(
                    $value,
                    ENT_QUOTES,
                    'UTF-8',
                ).'</td>';
            }
            $html .= '</tr>';
            $counter++;
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
        $mpdf->SetTitle(get_lang('Users'));
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
