<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\PkgResourceDependencies;

class CcAssesmentQuestionProcBase
{
    protected $quiz;
    protected $questions;
    protected $manifest;
    protected $section;
    protected $questionNode;
    protected $rootpath;
    protected $contextid;
    protected $outdir;
    protected $qtype;
    protected $qmetadata;
    protected $qitem;
    protected $qpresentation;
    protected $qresponseLid;
    protected $qresprocessing;
    protected $correct_grade_value;
    protected $correctAnswerNodeId;
    protected $correctAnswerIdent;
    protected $totalGradeValue;
    protected $answerlist;
    protected $generalFeedback;
    protected $correctFeedbacks = [];
    protected $incorrectFeedbacks = [];

    /**
     * @param XMLGenericDocument   $questions
     * @param cc_assesment_section $section
     * @param string               $rootpath
     * @param string               $contextid
     * @param string               $outdir
     * @param mixed                $quiz
     * @param mixed                $questionNode
     */
    public function __construct(&$quiz, &$questions, CcManifest &$manifest, CcAssesmentSection &$section, &$questionNode, $rootpath, $contextid, $outdir)
    {
        $this->quiz = $quiz;
        $this->questions = $questions;
        $this->manifest = $manifest;
        $this->section = $section;
        $this->questionNode = $questionNode;
        $this->rootpath = $rootpath;
        $this->contextid = $contextid;
        $this->outdir = $outdir;
        $qitem = new CcAssesmentSectionItem();
        $this->section->addItem($qitem);
        $qitem->setTitle($questionNode->question);
        $this->qitem = $qitem;
    }

    public function onGenerateMetadata(): void
    {
        if (empty($this->qmetadata)) {
            $this->qmetadata = new CcQuestionMetadata($this->qtype);
            // Get weighting value
            $weightingValue = $this->questionNode->ponderation;

            if ($weightingValue > 1) {
                $this->qmetadata->setWeighting($weightingValue);
            }

            // Get category
            if (!empty($this->questionNode->questionCategory)) {
                $this->qmetadata->setCategory($this->questionNode->questionCategory);
            }
            $rts = new CcAssesmentItemmetadata();
            $rts->addMetadata($this->qmetadata);
            $this->qitem->setItemmetadata($rts);
        }
    }

    public function onGeneratePresentation(): void
    {
        if (empty($this->qpresentation)) {
            $qpresentation = new CcAssesmentPresentation();
            $this->qitem->setPresentation($qpresentation);
            // add question text
            $qmaterial = new CcAssesmentMaterial();
            $qmattext = new CcAssesmentMattext();

            $questionText = $this->questionNode->question.'<br>'.$this->questionNode->description;
            $result = CcHelpers::processLinkedFiles(
                $questionText,
                $this->manifest,
                $this->rootpath,
                $this->contextid,
                $this->outdir
            );

            $qmattext->setContent($result[0], CcQtiValues::HTMLTYPE);
            $qmaterial->setMattext($qmattext);
            $qpresentation->setMaterial($qmaterial);
            $this->qpresentation = $qpresentation;
            PkgResourceDependencies::instance()->add($result[1]);
        }
    }

    public function onGenerateAnswers(): void {}

    public function onGenerateFeedbacks(): void
    {
        $generalQuestionFeedback = '';

        if (empty($generalQuestionFeedback)) {
            return;
        }

        $name = 'general_fb';
        // Add question general feedback - the one that should be always displayed
        $result = CcHelpers::processLinkedFiles(
            $generalQuestionFeedback,
            $this->manifest,
            $this->rootpath,
            $this->contextid,
            $this->outdir
        );

        CcAssesmentHelper::addFeedback(
            $this->qitem,
            $result[0],
            CcQtiValues::HTMLTYPE,
            $name
        );

        PkgResourceDependencies::instance()->add($result[1]);
        $this->generalFeedback = $name;
    }

    public function onGenerateResponseProcessing(): void
    {
        $qresprocessing = new CcAssesmentResprocessingtype();
        $this->qitem->addResprocessing($qresprocessing);
        $qdecvar = new CcAssesmentDecvartype();
        $qresprocessing->setDecvar($qdecvar);
        // according to the Common Cartridge 1.1 Profile: Implementation document
        // this should always be set to 0, 100 in case of question type that is not essay
        $qdecvar->setLimits(0, 100);
        $qdecvar->setVartype(CcQtiValues::DECIMAL);

        $this->qresprocessing = $qresprocessing;
    }

    public function generate(): void
    {
        $this->onGenerateMetadata();

        $this->onGeneratePresentation();

        $this->onGenerateAnswers();

        $this->onGenerateFeedbacks();

        $this->onGenerateResponseProcessing();
    }
}
