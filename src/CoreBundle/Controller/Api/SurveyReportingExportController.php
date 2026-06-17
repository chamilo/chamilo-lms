<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\State\Survey\SurveyReportingProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SurveyReportingExportController extends AbstractController
{
    public function __construct(
        private readonly SurveyReportingProvider $surveyReportingProvider,
    ) {}

    #[Route('/api/survey/reporting/{surveyId}/export.csv', name: 'api_survey_reporting_export_csv', methods: ['GET'])]
    public function csv(int $surveyId, Request $request): StreamedResponse
    {
        $course = $this->surveyReportingProvider->getCourse($request);
        $session = $this->surveyReportingProvider->getSession($request);
        $survey = $this->surveyReportingProvider->getSurveyFromCurrentContext($surveyId, $course, $session);

        return $this->surveyReportingProvider->exportCsv(
            $survey,
            $course,
            $session,
            $request,
            $request->query->getBoolean('compact')
        );
    }

    #[Route('/api/survey/reporting/{surveyId}/export.xlsx', name: 'api_survey_reporting_export_xlsx', methods: ['GET'])]
    public function xlsx(int $surveyId, Request $request): BinaryFileResponse
    {
        $course = $this->surveyReportingProvider->getCourse($request);
        $session = $this->surveyReportingProvider->getSession($request);
        $survey = $this->surveyReportingProvider->getSurveyFromCurrentContext($surveyId, $course, $session);

        return $this->surveyReportingProvider->exportXlsx($survey, $course, $session, $request);
    }

    #[Route('/api/survey/reporting/{surveyId}/export-by-class.xlsx', name: 'api_survey_reporting_export_by_class_xlsx', methods: ['GET'])]
    public function xlsxByClass(int $surveyId, Request $request): BinaryFileResponse
    {
        $course = $this->surveyReportingProvider->getCourse($request);
        $session = $this->surveyReportingProvider->getSession($request);
        $survey = $this->surveyReportingProvider->getSurveyFromCurrentContext($surveyId, $course, $session);

        return $this->surveyReportingProvider->exportByClassXlsx($survey, $course, $session, $request);
    }

    #[Route('/api/survey/reporting/{surveyId}/export.zip', name: 'api_survey_reporting_export_zip', methods: ['GET'])]
    public function zip(int $surveyId, Request $request): BinaryFileResponse
    {
        $course = $this->surveyReportingProvider->getCourse($request);
        $session = $this->surveyReportingProvider->getSession($request);
        $survey = $this->surveyReportingProvider->getSurveyFromCurrentContext($surveyId, $course, $session);

        return $this->surveyReportingProvider->exportPackageZip($survey, $course, $session, $request);
    }
}
