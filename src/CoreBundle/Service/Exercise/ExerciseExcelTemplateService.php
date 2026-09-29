<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Generates the modern Excel import template used by the exercise import screen.
 *
 * The rows match the parser in ExerciseQuestionImportProcessor and replace the
 * old static quiz template download without changing the
 * expected import format.
 */
final readonly class ExerciseExcelTemplateService
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private UserHelper $userHelper,
    ) {}

    public function downloadTemplate(): BinaryFileResponse
    {
        $this->assertCanDownloadTemplate();

        $spreadsheet = $this->createTemplateSpreadsheet();
        $path = $this->createTemporaryFilePath();

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($path));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'quiz_template.xlsx');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function assertCanDownloadTemplate(): void
    {
        if (!$this->userHelper->isTeacherOfCurrentCourse()) {
            throw new AccessDeniedHttpException('You are not allowed to download the exercise import template in this context.');
        }

        // The template itself carries no course data, so the course is only required to
        // pin the request to a context the teacher role was resolved against.
        $this->cidReqHelper->requireDoctrineCourseEntity();
    }

    private function createTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Quiz');

        $rows = [
            ['Quiz', 'Imported Excel quiz', ''],
            ['Question', 'What is PHP?', ''],
            ['Answer 1', 'A programming language', 'x'],
            ['Answer 2', 'A database', ''],
            ['Score', '', '10'],
            ['FeedbackTrue', 'Correct.', ''],
            ['FeedbackFalse', 'Incorrect.', ''],
            ['Category', 'PHP basics', ''],
            ['QuestionType', '', '1'],
            ['', '', ''],
            ['Question', 'Select the web technologies.', ''],
            ['Answer 1', 'HTML', 'x'],
            ['Answer 2', 'CSS', 'x'],
            ['Answer 3', 'SQL', ''],
            ['Score', '', '10'],
            ['QuestionType', '', '2'],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $sheet->setCellValue('A'.$rowNumber, $row[0]);
            $sheet->setCellValue('B'.$rowNumber, $row[1]);
            $sheet->setCellValue('C'.$rowNumber, $row[2]);
        }

        foreach (['A', 'B', 'C'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function createTemporaryFilePath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'chamilo_exercise_excel_template_');
        if (false === $path) {
            throw new BadRequestHttpException('The Excel template file could not be created.');
        }

        return $path;
    }
}
