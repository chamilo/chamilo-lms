<?php
/* For licensing terms, see /license.txt */

class CcConverterQuiz extends CcConverters 
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path) {
        $this->cc_type     = CcVersion13::assessment;
        $this->defaultfile = 'quiz.xml';
        $this->defaultname = assesment13_resource_file::deafultname;
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objQuizz) {
        
        $rt = new assesment13_resource_file();

        $title = $objQuizz['title'];
        
        $rt->set_title($title);

        
        // Metadata.
        $metadata = new cc_assesment_metadata();
        $rt->set_metadata($metadata);
        $metadata->enable_feedback();
        $metadata->enable_hints();
        $metadata->enable_solutions();
        // Attempts.
        $max_attempts = $objQuizz['max_attempt'];
        
        if ($max_attempts > 0) {
            // Qti does not support number of specific attempts bigger than 5 (??)
            if ($max_attempts > 5) {
                $max_attempts = cc_qti_values::unlimited;
            }
            $metadata->set_maxattempts($max_attempts);
        }
        
        // Time limit must be converted into minutes.
        $timelimit = $objQuizz['expired_time'];
        
        if ($timelimit > 0) {
            $metadata->set_timelimit($timelimit);
            $metadata->enable_latesubmissions(false);
        }

        $contextid = $objQuizz['source_id'];
        
        $result = CcHelpers::process_linked_files( $objQuizz['comment'],
                                                    $this->manifest,
                                                    $this->rootpath,
                                                    $contextid,
                                                    $outdir);

        cc_assesment_helper::add_assesment_description($rt, $result[0], cc_qti_values::htmltype);

        // Section.
        $section = new cc_assesment_section();
        $rt->set_section($section);

        // Process the actual questions.
        $ndeps = cc_assesment_helper::process_questions($objQuizz,
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
