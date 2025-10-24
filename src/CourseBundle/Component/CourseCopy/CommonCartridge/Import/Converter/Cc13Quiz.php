<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter;

use Answer;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Cc1p3Convert;
use DOMDocument;
use DOMXPath;
use Exercise;
use Question;
use Security;

use const DIRECTORY_SEPARATOR;

/**
 * CC 1.3 Quiz importer (namespace-agnostic / no legacy constants).
 * - Uses string keys ('quiz', 'question_bank') created by Cc1p3Convert::$instances.
 * - Accepts CC profile strings and normalizes (e.g., "cc.multiple_choice.v0p1" -> "multiple_choice").
 */
class Cc13Quiz extends Cc13Entities
{
    /**
     * Extracts quiz data (non-question-bank) from QTI assessments.
     */
    public function generateData()
    {
        $data = [];
        $instances = $this->generateInstances();
        if (!empty($instances)) {
            foreach ($instances as $instance) {
                if (0 === (int) ($instance['is_question_bank'] ?? 0)) {
                    $data[] = $this->getQuizData($instance);
                }
            }
        }

        return $data;
    }

    /**
     * Creates a Chamilo quiz (Exercise) and inserts questions/answers.
     *
     * @param array $quiz
     */
    public function storeQuiz($quiz): void
    {
        // Replace $IMS/1EdTech-CC-FILEBASE$ with course document path.
        $token = '/\$(?:IMS|1EdTech)[-_]CC[-_]FILEBASE\$\.\.\//';
        $courseInfo = api_get_course_info();

        // Path in Documents where we place Common Cartridge files (matches storeDocuments()).
        $replacementPath = '/courses/'.$courseInfo['directory'].'/document/commoncartridge/';

        $exercise = new Exercise($courseInfo['real_id']);
        $title = Exercise::format_title_variable($quiz['title']);
        $exercise->updateTitle($title);

        $description = preg_replace($token, $replacementPath, (string) $quiz['description']);
        $exercise->updateDescription($description);

        $exercise->updateAttempts((int) ($quiz['max_attempts'] ?? 0));
        $exercise->updateFeedbackType(0);
        $exercise->setRandom(0);

        // Respect shuffleanswers if provided; default to off.
        $exercise->updateRandomAnswers(!empty($quiz['shuffleanswers']));

        $exercise->updateExpiredTime((int) ($quiz['timelimit'] ?? 0));
        $exercise->updateType(1);

        // Persist the new Exercise.
        $exercise->save();

        if (!empty($quiz['questions'])) {
            foreach ($quiz['questions'] as $question) {
                $qtype = $question['type'];

                // Map our internal string types to Chamilo constants.
                $types = [
                    'unique_answer' => UNIQUE_ANSWER,
                    'multiple_answer' => MULTIPLE_ANSWER,
                    'fib' => FILL_IN_BLANKS,
                    'essay' => FREE_ANSWER,
                ];
                if (!isset($types[$qtype])) {
                    // Unknown question type; skip gracefully. // Tipo desconocido: ignorar sin romper.
                    continue;
                }
                $questionType = $types[$qtype];

                $questionInstance = Question::getInstance($questionType);
                if (empty($questionInstance)) {
                    continue;
                }

                $questionInstance->updateTitle(substr(
                    Security::remove_XSS(strip_tags_blacklist($question['title'], ['br', 'p'])),
                    0,
                    20
                ));

                $questionText = Security::remove_XSS(strip_tags_blacklist($question['title'], ['br', 'p']));
                // Replace placeholder to real Chamilo path inside question text.
                $questionText = preg_replace($token, $replacementPath, $questionText);
                $questionInstance->updateDescription($questionText);

                $questionInstance->updateLevel(1);
                $questionInstance->updateCategory(0);

                // Save normal question if NOT media
                if (MEDIA_QUESTION != $questionInstance->type) {
                    $questionInstance->save($exercise);
                    $exercise->addToList($questionInstance->iid);
                    $exercise->update_question_positions();
                }

                if ('unique_answer' === $qtype) {
                    $objAnswer = new Answer($questionInstance->iid);
                    $questionWeighting = 0.0;

                    foreach ($question['answers'] as $slot => $answerValues) {
                        $correct = !empty($answerValues['score']) ? (int) $answerValues['score'] : 0;
                        $answer = Security::remove_XSS((string) preg_replace($token, $replacementPath, (string) ($answerValues['title'] ?? '')));
                        $comment = Security::remove_XSS((string) preg_replace($token, $replacementPath, (string) ($answerValues['feedback'] ?? '')));
                        $weighting = (float) ($answerValues['score'] ?? 0);
                        $weighting = abs($weighting);
                        if ($weighting > 0) {
                            $questionWeighting += $weighting;
                        }
                        $goodAnswer = $correct ? true : false;

                        $objAnswer->createAnswer(
                            $answer,
                            $goodAnswer,
                            $comment,
                            $weighting,
                            $slot + 1,
                            null,
                            null,
                            ''
                        );
                    }
                    // Save answers and update question weighting.
                    $objAnswer->save();
                    $questionInstance->updateWeighting($questionWeighting);
                    $questionInstance->save($exercise);
                } else {
                    // Multiple-answer, FIB, essay, etc.
                    $objAnswer = new Answer($questionInstance->iid);
                    $questionWeighting = 0.0;

                    if (\is_array($question['answers'])) {
                        foreach ($question['answers'] as $slot => $answerValues) {
                            $answer = Security::remove_XSS((string) preg_replace($token, $replacementPath, (string) ($answerValues['title'] ?? '')));
                            $comment = Security::remove_XSS((string) preg_replace($token, $replacementPath, (string) ($answerValues['feedback'] ?? '')));
                            $weighting = (float) ($answerValues['score'] ?? 0);
                            if ($weighting > 0) {
                                $questionWeighting += $weighting;
                            }
                            $goodAnswer = ($weighting > 0);

                            $objAnswer->createAnswer(
                                $answer,
                                $goodAnswer,
                                $comment,
                                $weighting,
                                $slot + 1,
                                null,
                                null,
                                ''
                            );
                        }
                    } elseif ('essay' === $qtype) {
                        $questionWeighting = (float) ($question['ponderation'] ?? 1);
                    }

                    $objAnswer->save();
                    $questionInstance->updateWeighting($questionWeighting);
                    $questionInstance->save($exercise);
                }
            }
        }
    }

    public function storeQuizzes($quizzes): void
    {
        if (!empty($quizzes)) {
            foreach ($quizzes as $quiz) {
                $this->storeQuiz($quiz);
            }
        }
    }

    public function getQuizData($instance)
    {
        $values = [];
        if (!empty($instance)) {
            $questions = [];
            if (!empty($instance['questions'])) {
                foreach ($instance['questions'] as $question) {
                    $questions[$question['id']] = [
                        'title' => $question['title'],
                        'type' => $question['qtype'],
                        'ponderation' => $question['defaultgrade'],
                        'answers' => $question['answers'],
                    ];
                }
            }
            $values = [
                'id' => $instance['id'],
                'title' => $instance['title'],
                'description' => $instance['description'],
                'timelimit' => $instance['options']['timelimit'],
                'max_attempts' => $instance['options']['max_attempts'],
                'questions' => $questions,
            ];
        }

        return $values;
    }

    /**
     * Build instances from Cc1p3Convert::$instances for both assessments and banks.
     * Uses string keys as produced by the converter ('quiz', 'question_bank').
     */
    private function generateInstances()
    {
        $lastInstanceId = 0;
        $lastQuestionId = 0;
        $lastAnswerId = 0;

        $instances = [];

        // Keys as filled by Cc1p3Convert::createInstances()
        $types = ['quiz', 'question_bank'];

        foreach ($types as $type) {
            if (empty(Cc1p3Convert::$instances['instances'][$type])) {
                continue;
            }
            foreach (Cc1p3Convert::$instances['instances'][$type] as $instance) {
                $is_question_bank = ('quiz' === $type) ? 0 : 1;

                // Path to assessment.xml
                $assessmentFile = $this->getExternalXml($instance['resource_identifier']);

                if (empty($assessmentFile)) {
                    continue;
                }

                $assessment = $this->loadXmlResource(
                    Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$assessmentFile
                );

                if (empty($assessment)) {
                    continue;
                }

                Cc1p3Convert::logAction(
                    'QTI loaded',
                    [
                        'resource' => $assessmentFile,
                        'rootNS' => (string) ($assessment->documentElement?->namespaceURI ?? ''),
                    ]
                );

                $replaceValues = ['unlimited' => 0];

                $questions = $this->getQuestions($assessment, $lastQuestionId, $lastAnswerId, \dirname($assessmentFile), $is_question_bank);
                $questionCount = \is_array($questions) ? \count($questions) : 0;

                Cc1p3Convert::logAction(
                    'QTI questions detected',
                    [
                        'resource' => $assessmentFile,
                        'count' => (int) $questionCount,
                    ]
                );

                if ($questionCount > 0) {
                    $lastInstanceId++;

                    $instances[$instance['resource_identifier']]['questions'] = $questions;
                    $instances[$instance['resource_identifier']]['id'] = $lastInstanceId;
                    $instances[$instance['resource_identifier']]['title'] = $instance['title'];
                    $instances[$instance['resource_identifier']]['description'] = $this->getQuizDescription($assessment);
                    $instances[$instance['resource_identifier']]['is_question_bank'] = $is_question_bank;
                    $instances[$instance['resource_identifier']]['options']['timelimit'] = $this->getGlobalConfig($assessment, 'qmd_timelimit', 0);
                    $instances[$instance['resource_identifier']]['options']['max_attempts'] = $this->getGlobalConfig($assessment, 'cc_maxattempts', 0, $replaceValues);
                }
            }
        }

        return $instances;
    }

    private function getGlobalConfig($assessment, $option, $defaultValue, $replaceValues = '')
    {
        $xp = $this->xp($assessment);
        $nodes = $xp->query(
            '/*[local-name()="questestinterop"]/*[local-name()="assessment"]'
            .'/*[local-name()="qtimetadata"]/*[local-name()="qtimetadatafield"]'
        );

        $response = '';
        foreach ($nodes as $field) {
            $label = $xp->query('*[local-name()="fieldlabel"]/text()', $field)->item(0)?->nodeValue ?? '';
            if (0 === strcasecmp((string) $label, (string) $option)) {
                $response = $xp->query('*[local-name()="fieldentry"]/text()', $field)->item(0)?->nodeValue ?? '';

                break;
            }
        }

        $response = trim((string) $response);

        if (!empty($replaceValues)) {
            foreach ($replaceValues as $key => $value) {
                $response = ($key == $response) ? (string) $value : $response;
            }
        }

        return ('' === $response) ? $defaultValue : $response;
    }

    private function getQuizDescription(DOMDocument $assessment): string
    {
        $xp = $this->xp($assessment);
        $n = $xp->query(
            '/*[local-name()="questestinterop"]/*[local-name()="assessment"]'
            .'/*[local-name()="rubric"]/*[local-name()="material"]/*[local-name()="mattext"]/text()'
        );

        return $n && $n->length > 0 ? (string) $n->item(0)->nodeValue : '';
    }

    private function getQuestions($assessment, &$lastQuestionId, &$last_answer_id, $rootPath, $is_question_bank)
    {
        $questions = [];
        $xp = $this->xp($assessment);

        $itemPath = $is_question_bank
            ? '/*[local-name()="questestinterop"]/*[local-name()="objectbank"]/*[local-name()="item"]'
            : '/*[local-name()="questestinterop"]/*[local-name()="assessment"]/*[local-name()="section"]/*[local-name()="item"]';

        $items = $xp->query($itemPath);

        foreach ($items as $item) {
            $questionIdentifier = trim((string) ($item->getAttribute('ident') ?? ''));
            if ('' === $questionIdentifier) {
                continue;
            }

            // Title inside <presentation> (with/without <flow>)
            $titleNode = $xp->query(
                '(*[local-name()="presentation"]/*[local-name()="flow"]/*[local-name()="material"]/*[local-name()="mattext"]'
                .' | *[local-name()="presentation"]/*[local-name()="material"]/*[local-name()="mattext"])[1]/text()',
                $item
            );
            $questionTitle = $titleNode->item(0)?->nodeValue ?? '';

            $qTypeInfo = $this->getQuestionType($questionIdentifier, $assessment);
            if (empty($qTypeInfo['qtype'])) {
                continue;
            }

            $lastQuestionId++;
            $questions[$questionIdentifier]['id'] = $lastQuestionId;

            $questionTitle = $this->updateSources($questionTitle, $rootPath);
            $questionTitle = '' !== $questionTitle ? str_replace('%24', '$', $this->includeTitles($questionTitle)) : '';

            $questionname = $item->getAttribute('title') ?? '';

            $questions[$questionIdentifier]['title'] = $questionTitle;
            $questions[$questionIdentifier]['name'] = $questionname;
            $questions[$questionIdentifier]['identifier'] = $questionIdentifier;
            $questions[$questionIdentifier]['qtype'] = $qTypeInfo['qtype']; // 'unique_answer', 'multiple_answer', 'fib', 'essay'
            $questions[$questionIdentifier]['cc_type'] = $qTypeInfo['cc'];    // raw CC type string
            $questions[$questionIdentifier]['feedback'] = $this->getGeneralFeedback($assessment, $questionIdentifier);
            $questions[$questionIdentifier]['defaultgrade'] = $this->getDefaultgrade($assessment, $questionIdentifier);
            $questions[$questionIdentifier]['answers'] = $this->getAnswers($questionIdentifier, $assessment, $last_answer_id);
        }

        return !empty($questions) ? $questions : '';
    }

    private function getDefaultgrade($assessment, $questionIdentifier)
    {
        $xp = $this->xp($assessment);
        $n = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'//*[local-name()="qtimetadatafield"][*[local-name()="fieldlabel" and text()="cc_weighting"]]'
            .'/*[local-name()="fieldentry"]/text()'
        );
        $result = 1;
        if ($n && $n->length > 0) {
            $resp = (int) $n->item(0)->nodeValue;
            if ($resp >= 0 && $resp <= 99) {
                $result = $resp;
            }
        }

        return $result;
    }

    private function getGeneralFeedback($assessment, $questionIdentifier)
    {
        $xp = $this->xp($assessment);

        $respconditions = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"]'
        );

        $feedbackIds = [];
        foreach ($respconditions as $rc) {
            $cont = strtolower((string) ($rc->getAttribute('continue') ?? ''));
            if ('yes' === $cont) {
                $dfs = $xp->query('*[local-name()="displayfeedback"]', $rc);
                foreach ($dfs as $df) {
                    $link = $df->getAttribute('linkrefid') ?? '';
                    if ('' !== $link) {
                        $feedbackIds[] = $link;
                    }
                }
            }
        }

        $feedback = '';
        foreach ($feedbackIds as $fid) {
            $texts = $xp->query(
                '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
                .'/*[local-name()="itemfeedback" and @ident="'.$fid.'"]'
                .'/*[local-name()="flow_mat"]/*[local-name()="material"]/*[local-name()="mattext"]/text()'
            );
            if ($texts && $texts->length > 0) {
                $feedback .= $texts->item(0)->nodeValue.' ';
            }
        }

        return trim($feedback);
    }

    private function getFeedback($assessment, $identifier, $itemIdentifier, $questionType)
    {
        $xp = $this->xp($assessment);

        $rcs = $xp->query(
            '//*[local-name()="item" and @ident="'.$itemIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"]'
        );

        $ids = [];
        foreach ($rcs as $rc) {
            $ve = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"]/text()', $rc)
                ->item(0)?->nodeValue ?? ''
            ;
            if (0 === strcasecmp((string) $ve, (string) $identifier) || ('essay' === $questionType)) {
                $dfs = $xp->query('*[local-name()="displayfeedback"]', $rc);
                foreach ($dfs as $df) {
                    $link = $df->getAttribute('linkrefid') ?? '';
                    if ('' !== $link) {
                        $ids[] = $link;
                    }
                }
            }
        }

        $feedback = '';
        foreach ($ids as $fid) {
            $texts = $xp->query(
                '//*[local-name()="item" and @ident="'.$itemIdentifier.'"]'
                .'/*[local-name()="itemfeedback" and @ident="'.$fid.'"]'
                .'/*[local-name()="flow_mat"]/*[local-name()="material"]/*[local-name()="mattext"]/text()'
            );
            if ($texts && $texts->length > 0) {
                $feedback .= $texts->item(0)->nodeValue.' ';
            }
        }

        return trim($feedback);
    }

    /**
     * Namespace-agnostic FIB answers (Fill-in-the-blank).
     *
     * @param mixed $questionIdentifier
     * @param mixed $identifier
     * @param mixed $assessment
     * @param mixed $lastAnswerId
     */
    private function getAnswersFib($questionIdentifier, $identifier, $assessment, &$lastAnswerId)
    {
        $xp = $this->xp($assessment);

        $correct = [];
        $incorrect = [];

        // All respconditions for this item
        $responseItems = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"]'
        );

        // Find the condition with setvar=100 (correct) and collect varequal values
        $correctResp = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"][*[local-name()="setvar" and normalize-space(text())="100"]]'
        );

        if ($correctResp && $correctResp->length > 0) {
            $canswers = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"]/text()', $correctResp->item(0));
            foreach ($canswers as $cnode) {
                $answertitle = trim((string) $cnode->nodeValue);
                if ('' === $answertitle) {
                    continue;
                }
                $lastAnswerId++;
                $correct[$answertitle] = [
                    'id' => $lastAnswerId,
                    'title' => $answertitle,
                    'score' => 1,
                    'feedback' => '',
                    'case' => 0,
                ];
            }
        }

        // Iterate through all respconditions to attach feedback and collect incorrects
        foreach ($responseItems as $rc) {
            // Skip the correct one (setvar=100) here
            $sv = $xp->query('*[local-name()="setvar"]/text()', $rc)->item(0)?->nodeValue ?? null;
            if (null !== $sv && '100' === trim($sv)) {
                continue;
            }

            $ve = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"]/text()', $rc);
            if (!$ve || 0 === $ve->length) {
                continue;
            }
            $answerTitle = trim((string) $ve->item(0)->nodeValue);

            // Gather feedback ids
            $dfs = $xp->query('*[local-name()="displayfeedback"]', $rc);
            $fbids = [];
            foreach ($dfs as $df) {
                $link = $df->getAttribute('linkrefid') ?? '';
                if ('' !== $link) {
                    $fbids[] = $link;
                }
            }

            // Resolve feedback text(s)
            $feedback = '';
            foreach ($fbids as $fid) {
                $fbt = $xp->query(
                    '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
                    .'/*[local-name()="itemfeedback" and @ident="'.$fid.'"]'
                    .'/*[local-name()="flow_mat"]/*[local-name()="material"]/*[local-name()="mattext"]/text()'
                );
                if ($fbt && $fbt->length > 0) {
                    $feedback .= $fbt->item(0)->nodeValue.' ';
                }
            }
            $feedback = trim($feedback);

            if (\array_key_exists($answerTitle, $correct)) {
                $correct[$answerTitle]['feedback'] = $feedback;
            } else {
                $lastAnswerId++;
                $incorrect[] = [
                    'id' => $lastAnswerId,
                    'title' => $answerTitle,
                    'score' => 0,
                    'feedback' => $feedback,
                    'case' => 0,
                ];
            }
        }

        $answers = array_merge($correct, $incorrect);

        return empty($answers) ? '' : $answers;
    }

    /**
     * Namespace-agnostic Pattern Match answers.
     *
     * @param mixed $questionIdentifier
     * @param mixed $identifier
     * @param mixed $assessment
     * @param mixed $lastAnswerId
     */
    private function getAnswersPatternMatch($questionIdentifier, $identifier, $assessment, &$lastAnswerId)
    {
        $xp = $this->xp($assessment);
        $answers = [];

        $responseItems = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"]'
        );

        foreach ($responseItems as $rc) {
            $sv = $xp->query('*[local-name()="setvar"]/text()', $rc)->item(0)?->nodeValue ?? '';
            $sv = trim((string) $sv);

            if ('' !== $sv) {
                $lastAnswerId++;

                // varequal with respident or varsubstring
                $answerTitle = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"][@respident="'.$identifier.'"]/text()', $rc)->item(0)?->nodeValue ?? '';
                $answerTitle = trim((string) $answerTitle);

                if ('' === $answerTitle) {
                    $sub = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varsubstring"][@respident="'.$identifier.'"]/text()', $rc)->item(0)?->nodeValue ?? '';
                    $sub = trim((string) $sub);
                    $answerTitle = '' !== $sub ? ('*'.$sub.'*') : '';
                }
                if ('' === $answerTitle) {
                    $answerTitle = '*';
                }

                $caseAttr = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"]/@case', $rc)->item(0)?->nodeValue ?? 'no';
                $case = 'yes' === strtolower((string) $caseAttr) ? 1 : 0;

                // Feedback
                $dfs = $xp->query('*[local-name()="displayfeedback"]', $rc);
                $fbids = [];
                foreach ($dfs as $df) {
                    $link = $df->getAttribute('linkrefid') ?? '';
                    if ('' !== $link) {
                        $fbids[] = $link;
                    }
                }
                $feedback = '';
                foreach ($fbids as $fid) {
                    $fbt = $xp->query(
                        '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
                        .'/*[local-name()="itemfeedback" and @ident="'.$fid.'"]'
                        .'/*[local-name()="flow_mat"]/*[local-name()="material"]/*[local-name()="mattext"]/text()'
                    );
                    if ($fbt && $fbt->length > 0) {
                        $feedback .= $fbt->item(0)->nodeValue.' ';
                    }
                }

                $answers[] = [
                    'id' => $lastAnswerId,
                    'title' => $answerTitle,
                    'score' => $sv,
                    'feedback' => trim($feedback),
                    'case' => $case,
                ];
            }
        }

        return empty($answers) ? '' : $answers;
    }

    private function getAnswers($identifier, $assessment, &$lastAnswerId)
    {
        $xp = $this->xp($assessment);
        $answers = [];

        $tinfo = $this->getQuestionType($identifier, $assessment);
        $ccType = $tinfo['cc']; // e.g. 'multiple_choice', 'multiple_response', 'fib', 'pattern_match', 'essay'
        $isMultiresponse = str_contains($ccType, 'multiple_response');

        if (str_contains($ccType, 'fib') || str_contains($ccType, 'pattern_match')) {
            // Find response_str ident first
            $aid = $xp->query(
                '//*[local-name()="item" and @ident="'.$identifier.'"]'
                .'/*[local-name()="presentation"]//*[local-name()="response_str"]/@ident'
            )->item(0)?->nodeValue ?? '';
            if ('' === $aid) {
                return '';
            }

            if (str_contains($ccType, 'fib')) {
                return $this->getAnswersFib($identifier, $aid, $assessment, $lastAnswerId);
            }

            return $this->getAnswersPatternMatch($identifier, $aid, $assessment, $lastAnswerId);
        }

        if (str_contains($ccType, 'essay')) {
            return '';
        }

        // multiple_choice / true_false / multiple_response
        $labels = $xp->query(
            '//*[local-name()="item" and @ident="'.$identifier.'"]'
            .'/*[local-name()="presentation"]'
            .'/*[local-name()="response_lid"]/*[local-name()="render_choice"]/*[local-name()="response_label"]'
            .' | '
            .'//*[local-name()="item" and @ident="'.$identifier.'"]'
            .'/*[local-name()="presentation"]/*[local-name()="flow"]'
            .'/*[local-name()="response_lid"]/*[local-name()="render_choice"]/*[local-name()="response_label"]'
        );

        $correctIds = [];
        $correctFrac = 1.0;

        if ($isMultiresponse) {
            // Determine how many options yield SCORE=100 to split equally.
            $c = $xp->query(
                '//*[local-name()="item" and @ident="'.$identifier.'"]'
                .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"][*[local-name()="setvar" and normalize-space(text())="100"]]'
                .'/*[local-name()="conditionvar"]/*[local-name()="varequal"]/text()'
            );
            $n = $c?->length ?? 0;
            if ($n > 0) {
                $correctFrac = round(1.0 / (float) $n, 7);
                foreach ($c as $node) {
                    $correctIds[trim((string) $node->nodeValue)] = true;
                }
            }
        }

        foreach ($labels as $lab) {
            $lastAnswerId++;
            $aid = $lab->getAttribute('ident') ?? '';
            $text = $xp->query('*[local-name()="material"]/*[local-name()="mattext"]/text()', $lab)->item(0)?->nodeValue ?? '';

            $feedback = $this->getFeedback($assessment, $aid, $identifier, $ccType);
            $score = $this->getScore($assessment, $aid, $identifier);

            if ($isMultiresponse && isset($correctIds[$aid])) {
                $score = $correctFrac;
            }

            $answers[] = [
                'id' => $lastAnswerId,
                'title' => $text,
                'score' => $score,
                'identifier' => $aid,
                'feedback' => $feedback,
            ];
        }

        return empty($answers) ? '' : $answers;
    }

    private function getScore($assessment, $identifier, $questionIdentifier)
    {
        $xp = $this->xp($assessment);

        $rcs = $xp->query(
            '//*[local-name()="item" and @ident="'.$questionIdentifier.'"]'
            .'/*[local-name()="resprocessing"]/*[local-name()="respcondition"]'
        );

        $scoreValue = null;
        foreach ($rcs as $rc) {
            $ve = $xp->query('*[local-name()="conditionvar"]/*[local-name()="varequal"]/text()', $rc)
                ->item(0)?->nodeValue ?? ''
            ;
            if (0 === strcasecmp((string) $ve, (string) $identifier)) {
                $sv = $xp->query('*[local-name()="setvar"]/text()', $rc)->item(0)?->nodeValue ?? null;
                if (null !== $sv) {
                    $scoreValue = trim($sv);

                    break;
                }
            }
        }

        // Normalize to [0,1] granularity expected later.
        if (null === $scoreValue) {
            return '0.0000000';
        }

        return ((float) $scoreValue > 0) ? '1.0000000' : '0.0000000';
    }

    /**
     * Reads cc_profile and maps to our internal qtypes.
     * Returns:
     *  - 'qtype' => one of: unique_answer, multiple_answer, fib, essay
     *  - 'cc'    => the normalized cc_profile string.
     *
     * @param mixed $identifier
     * @param mixed $assessment
     */
    private function getQuestionType($identifier, $assessment)
    {
        // Namespace-agnostic XPath
        $x = new DOMXPath($assessment);
        $metadata = $x->query(
            '//*[local-name()="item" and @ident="'.$identifier.'"]'.
            '/*[local-name()="itemmetadata"]/*[local-name()="qtimetadata"]/*[local-name()="qtimetadatafield"]'
        );

        $type = '';
        foreach ($metadata as $field) {
            $label = $x->query('./*[local-name()="fieldlabel"]/text()', $field);
            $lab = ($label && $label->length > 0) ? trim((string) $label->item(0)->nodeValue) : '';
            if (0 === strcasecmp($lab, 'cc_profile')) {
                $entry = $x->query('./*[local-name()="fieldentry"]/text()', $field);
                $type = ($entry && $entry->length > 0) ? trim((string) $entry->item(0)->nodeValue) : '';

                break;
            }
        }

        // Normalize patterns like "cc.multiple_choice.v0p1" -> "multiple_choice"
        $raw = $type;
        $type = preg_replace('~^cc\.~i', '', (string) $type);
        $type = preg_replace('~\.v\d+p\d+$~i', '', (string) $type);

        // Map to internal set
        $qtype = '';

        switch ($type) {
            case 'multiple_choice':
            case 'true_false':
                $qtype = 'unique_answer';

                break;

            case 'multiple_response':
                $qtype = 'multiple_answer';

                break;

            case 'fib':
            case 'pattern_match':
                $qtype = 'fib';

                break;

            case 'essay':
                $qtype = 'essay';

                break;

            default:
                $qtype = '';
        }

        Cc1p3Convert::logAction(
            'QTI cc_profile mapped',
            ['identifier' => $identifier, 'raw' => $raw, 'norm' => $type, 'qtype' => $qtype]
        );

        return ['qtype' => $qtype, 'cc' => $type];
    }

    /**
     * Build an NS-agnostic XPath (we'll use local-name() in expressions).
     */
    private function xp(DOMDocument $doc): DOMXPath
    {
        return new DOMXPath($doc);
    }
}
