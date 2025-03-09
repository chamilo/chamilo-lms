<?php

/* For licensing terms, see /license.txt */

/**
 * Class AnswerInOfficeDoc
 * Allows a question type where the answer is written in an Office document.
 *
 * @author Cristian
 */
class AnswerInOfficeDoc extends Question
{
    public $typePicture = 'options_evaluation.png';
    public $explanationLangVar = 'AnswerInOfficeDoc';
    public $sessionId;
    public $userId;
    public $exerciseId;
    public $exeId;
    private $storePath;
    private $fileName;
    private $filePath;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('true' !== OnlyofficePlugin::create()->get('enable_onlyoffice_plugin')) {
            throw new Exception(get_lang('OnlyOfficePluginRequired'));
        }

        parent::__construct();
        $this->type = ANSWER_IN_OFFICE_DOC;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Initialize the file path structure.
     */
    public function initFile(int $sessionId, int $userId, int $exerciseId, int $exeId, int $courseId = 0): void
    {
        if (!empty($courseId)) {
            $this->course = api_get_course_info_by_id($courseId);
        }

        $this->sessionId = $sessionId ?: 0;
        $this->userId = $userId;
        $this->exerciseId = $exerciseId ?: 0;
        $this->exeId = $exeId;

        $this->storePath = $this->generateDirectory();
        $this->fileName = $this->generateFileName();
        $this->filePath = $this->storePath . $this->fileName;
    }

    /**
     * Create form for uploading an Office document.
     */
    public function createAnswersForm($form): void
    {
        $allowedFormats = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',      // .xlsx
            'application/msword',                                                     // .doc
            'application/vnd.ms-excel'                                                // .xls
        ];

        $form->addElement('file', 'office_file', get_lang('UploadOfficeDoc'));
        $form->addRule('office_file', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('office_file', get_lang('InvalidFileFormat'), 'mimetype', $allowedFormats);

        $allowedExtensions = implode(', ', ['.docx', '.xlsx', '.doc', '.xls']);
        $form->addElement('static', 'file_hint', get_lang('AllowedFormats'), "<p>{$allowedExtensions}</p>");

        if (!empty($this->extra)) {
            $fileUrl = api_get_path(WEB_COURSE_PATH) . $this->course['path'] . "/exercises/" . $this->extra;
            $form->addElement('static', 'current_office_file', get_lang('CurrentOfficeDoc'), "<a href='{$fileUrl}' target='_blank'>{$this->extra}</a>");
        }

        $form->addText('weighting', get_lang('Weighting'), ['class' => 'span1']);

        global $text;
        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->iid)) {
            $form->setDefaults(['weighting' => float_format($this->weighting, 1)]);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults(['weighting' => '10']);
            }
        }
    }

    /**
     * Process the uploaded document and save it.
     */
    public function processAnswersCreation($form, $exercise): void
    {
        if (!empty($_FILES['office_file']['name'])) {
            $uploadDir = $this->generateDirectory();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $extension = pathinfo($_FILES['office_file']['name'], PATHINFO_EXTENSION);
            $filename = $this->generateFileName() . '.' . $extension;
            $filePath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['office_file']['tmp_name'], $filePath)) {
                $this->weighting = $form->getSubmitValue('weighting');
                $sessionId = !empty($this->sessionId) ? $this->sessionId : 0;
                $this->extra = "{$this->course['real_id']}/{$sessionId}/{$this->exerciseId}/{$this->iid}/" . $filename;
                $this->extra = preg_replace('/\/+/', '/', $this->extra);

                $this->save($exercise);
            }
        }
    }

    /**
     * Show the question in an exercise.
     */
    public function return_header(Exercise $exercise, $counter = null, $score = []): string
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<div class="question-container">';

        if (!empty($this->extra) && 'true' === OnlyofficePlugin::create()->get('enable_onlyoffice_plugin')) {
            $fileUrl = api_get_course_path() . "/exercises/" . $this->extra;
            $documentUrl = OnlyofficeTools::getPathToView($fileUrl);
            $header .= "<iframe src='{$documentUrl}' width='800' height='600'></iframe>";
        } else {
            $header .= '<p>' . get_lang('NoOfficeDocProvided') . '</p>';
        }

        $header .= '</div>';
        return $header;
    }

    /**
     * Generate the necessary directory for OnlyOffice documents.
     */
    private function generateDirectory(): string
    {
        $storePath = api_get_path(SYS_COURSE_PATH) . $this->course['path'] . '/exercises/';
        $sessionId = !empty($this->sessionId) ? $this->sessionId : 0;
        $finalPath = "{$storePath}{$this->course['real_id']}/{$sessionId}/{$this->exerciseId}/{$this->iid}/";

        if (!is_dir($finalPath)) {
            mkdir($finalPath, 0775, true);
        }

        return rtrim($finalPath, '/') . '/';
    }

    /**
     * Get the absolute file path. Returns null if the file doesn't exist.
     */
    public function getFileUrl(bool $loadFromDatabase = false): ?string
    {
        if ($loadFromDatabase) {
            $em = Database::getManager();
            $result = $em->getRepository('ChamiloCoreBundle:TrackEAttempt')->findOneBy([
                'exeId' => $this->exeId,
                'userId' => $this->userId,
                'questionId' => $this->iid,
                'sessionId' => $this->sessionId,
                'cId' => $this->course['real_id'],
            ]);

            if (!$result || empty($result->getFilename())) {
                return null;
            }

            $this->fileName = $result->getFilename();
        } else {
            if (empty($this->extra)) {
                return null;
            }

            $this->fileName = basename($this->extra);
        }

        $filePath =  $this->course['path'].'/exercises/'.$this->extra;
        if (is_file(api_get_path(SYS_COURSE_PATH).$filePath)) {
            return $filePath;
        }

        return null;
    }

    /**
     * Generate the file name for the OnlyOffice document.
     *
     * @return string
     */
    private function generateFileName(): string
    {
        return 'office_'.uniqid();
    }
}
