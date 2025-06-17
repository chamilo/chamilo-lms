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
    public function initFile(int $sessionId, int $userId, int $exerciseId, int $exeId): void
    {
        $this->sessionId = $sessionId ?: 0;
        $this->userId = $userId;
        $this->exerciseId = $exerciseId ?: 0;
        $this->exeId = $exeId;

        $this->storePath = $this->generateDirectory();
        $this->fileName = $this->generateFileName();
        $this->filePath = $this->storePath.$this->fileName;
    }

    /**
     * Create form for uploading an Office document.
     */
    public function createAnswersForm($form): void
    {
        if (!empty($this->exerciseList)) {
            $this->exerciseId = reset($this->exerciseList);
        }

        $allowedFormats = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',      // .xlsx
            'application/msword',                                                     // .doc
            'application/vnd.ms-excel',                                                // .xls
        ];

        $form->addElement('file', 'office_file', get_lang('UploadOfficeDoc'));
        $form->addRule('office_file', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('office_file', get_lang('InvalidFileFormat'), 'mimetype', $allowedFormats);

        $allowedExtensions = implode(', ', ['.docx', '.xlsx', '.doc', '.xls']);
        $form->addElement('static', 'file_hint', get_lang('AllowedFormats'), "<p>{$allowedExtensions}</p>");

        if (!empty($this->extra)) {
            $fileUrl = api_get_path(WEB_COURSE_PATH).$this->getStoredFilePath();
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
            $extension = pathinfo($_FILES['office_file']['name'], PATHINFO_EXTENSION);
            $tempFilename = "office_".uniqid().".".$extension;
            $tempPath = sys_get_temp_dir().'/'.$tempFilename;

            if (!move_uploaded_file($_FILES['office_file']['tmp_name'], $tempPath)) {
                return;
            }

            $this->weighting = $form->getSubmitValue('weighting');
            $this->extra = "";
            $this->save($exercise);

            $this->exerciseId = $exercise->iid;
            $uploadDir = $this->generateDirectory();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $filename = "office_".$this->iid.".".$extension;
            $filePath = $uploadDir.$filename;

            if (!rename($tempPath, $filePath)) {
                return;
            }

            $this->extra = $filename;
            $this->save($exercise);
        }
    }

    /**
     * Get the stored file path dynamically.
     */
    public function getStoredFilePath(): ?string
    {
        if (empty($this->extra)) {
            return null;
        }

        return "{$this->course['path']}/exercises/onlyoffice/{$this->exerciseId}/{$this->iid}/{$this->extra}";
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

            $this->fileName = $this->extra;
        }

        $filePath = $this->getStoredFilePath();

        if (is_file(api_get_path(SYS_COURSE_PATH).$filePath)) {
            return $filePath;
        }

        return null;
    }

    /**
     * Show the question in an exercise.
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">
            <tr>
                <th>'.get_lang("Answer").'</th>
            </tr>';

        return $header;
    }

    /**
     * Generate the necessary directory for OnlyOffice documents.
     */
    private function generateDirectory(): string
    {
        $exercisePath = api_get_path(SYS_COURSE_PATH).$this->course['path']."/exercises/onlyoffice/{$this->exerciseId}/{$this->iid}/";

        if (!is_dir($exercisePath)) {
            mkdir($exercisePath, 0775, true);
        }

        return rtrim($exercisePath, '/').'/';
    }

    /**
     * Generate the file name for the OnlyOffice document.
     */
    private function generateFileName(): string
    {
        return 'office_'.uniqid();
    }
}
