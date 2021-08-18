<?php
/* For licensing terms, see /license.txt */

class CcConverterQuiz extends CcConverters
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->ccType     = CcVersion13::assessment;
        $this->defaultfile = 'quiz.xml';
        $this->defaultname = Assesment13ResourceFile::deafultname;
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objQuizz)
    {

        $rt = new Assesment13ResourceFile();
        $title = $objQuizz['title'];
        $rt->setTitle($title);

        // Metadata.
        $metadata = new CcAssesmentMetadata();
        $rt->setMetadata($metadata);
        $metadata->enableFeedback();
        $metadata->enableHints();
        $metadata->enableSolutions();
        // Attempts.
        $max_attempts = $objQuizz['max_attempt'];

        if ($max_attempts > 0) {
            // Qti does not support number of specific attempts bigger than 5 (??)
            if ($max_attempts > 5) {
                $max_attempts = cc_qti_values::unlimited;
            }
            $metadata->setMaxattempts($max_attempts);
        }

        // Time limit must be converted into minutes.
        $timelimit = $objQuizz['expired_time'];

        if ($timelimit > 0) {
            $metadata->setTimelimit($timelimit);
            $metadata->enableLatesubmissions(false);
        }

        $contextid = $objQuizz['source_id'];

        $result = CcHelpers::processLinkedFiles($objQuizz['comment'],
                                                    $this->manifest,
                                                    $this->rootpath,
                                                    $contextid,
                                                    $outdir);

        CcAssesmentHelper::addAssesmentDescription($rt, $result[0], cc_qti_values::htmltype);

        // Section.
        $section = new CcAssesmentSection();
        $rt->setSection($section);
        // Process the actual questions.
        $ndeps = CcAssesmentHelper::processQuestions($objQuizz,
                                                        $this->manifest,
                                                        $section,
                                                        $this->rootpath,
                                                        $contextid,
                                                        $outdir
                                                    );

        if ($ndeps === false) {
            // No exportable questions in quiz or quiz has no questions
            // so just skip it.
            return true;
        }
        // Store any additional dependencies.
        $deps = array_merge($result[1], $ndeps);

        // Store everything.
        $this->store($rt, $outdir, $title, $deps);
        return true;
    }
}
